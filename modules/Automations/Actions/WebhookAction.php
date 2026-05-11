<?php

declare(strict_types=1);

namespace FlexCore\Modules\Automations\Actions;

use FlexCore\Modules\Automations\ActionHandlerInterface;

/**
 * WebhookAction — sends a POST request to an external URL.
 *
 * config shape:
 * {
 *   "url":     "https://...",
 *   "method":  "POST",         // POST | PUT | PATCH
 *   "headers": {},             // extra headers
 *   "body":    "full | diff"   // what to send
 * }
 *
 * Retry logic: 3 attempts with exponential backoff (1s, 2s, 4s).
 */
class WebhookAction implements ActionHandlerInterface
{
    public function execute(array $config, int $recordId, int $entityId, array $input): void
    {
        $url    = $config['url']    ?? '';
        $method = strtoupper($config['method'] ?? 'POST');

        if (empty($url)) {
            throw new \InvalidArgumentException('Webhook URL não configurada.');
        }

        $payload = json_encode([
            'event'      => 'record.' . ($config['_event'] ?? 'changed'),
            'record_id'  => $recordId,
            'entity_id'  => $entityId,
            'data'       => $input,
            'fired_at'   => date('c'),
        ]);

        $headers = array_merge([
            'Content-Type'   => 'application/json',
            'X-FlexCore-Key' => hash('sha256', $url . $recordId),
        ], $config['headers'] ?? []);

        $this->sendWithRetry($url, $method, $payload, $headers);
    }

    public function label(): string
    {
        return 'Enviar Webhook';
    }

    public function configSchema(): array
    {
        return [
            ['key' => 'url',    'type' => 'url',    'label' => 'URL do Webhook',  'required' => true],
            ['key' => 'method', 'type' => 'select', 'label' => 'Método HTTP',
             'options' => ['POST', 'PUT', 'PATCH'],  'default' => 'POST'],
        ];
    }

    // ── Internals ─────────────────────────────────────────────────────

    private function sendWithRetry(
        string $url,
        string $method,
        string $payload,
        array  $headers,
        int    $maxAttempts = 3,
    ): void {
        $attempt = 0;

        do {
            $attempt++;
            $success = $this->send($url, $method, $payload, $headers);

            if ($success) {
                return;
            }

            if ($attempt < $maxAttempts) {
                sleep(2 ** ($attempt - 1)); // 1s, 2s, 4s
            }
        } while ($attempt < $maxAttempts);

        throw new \RuntimeException("Webhook falhou após {$maxAttempts} tentativas: {$url}");
    }

    private function send(string $url, string $method, string $payload, array $headers): bool
    {
        $headerLines = array_map(
            fn($k, $v) => "{$k}: {$v}",
            array_keys($headers),
            array_values($headers)
        );

        $ctx = stream_context_create([
            'http' => [
                'method'        => $method,
                'header'        => implode("\r\n", $headerLines),
                'content'       => $payload,
                'timeout'       => 10,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);

        if ($response === false) {
            return false;
        }

        // Parse status code from $http_response_header
        $status = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $h, $m)) {
                $status = (int) $m[1];
                break;
            }
        }

        return $status >= 200 && $status < 300;
    }
}

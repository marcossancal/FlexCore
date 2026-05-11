<?php

namespace FlexCore\Api\Middleware;

use FlexCore\Core\Router\MiddlewareInterface;
use FlexCore\Core\Router\Request;

/**
 * ApiAuthMiddleware — valida API key + rate limiting sliding window.
 * Compatible: PHP 7.4+
 */
class ApiAuthMiddleware implements MiddlewareInterface
{
    const WINDOW_SECONDS = 60;

    public function handle(Request $request, callable $next): void
    {
        $rawKey = $this->extractKey($request);

        if ($rawKey === null) {
            $this->abort(401, 'API key ausente. Use o header Authorization: Bearer {key}.');
        }

        $key = \DB::one(
            'SELECT * FROM api_keys WHERE key_hash = ? AND active = 1',
            [hash('sha256', $rawKey)]
        );

        if ($key === null) {
            $this->abort(401, 'API key inválida ou inativa.');
        }

        if ($key['expires_at'] !== null && strtotime($key['expires_at']) < time()) {
            $this->abort(401, 'API key expirada.');
        }

        $this->checkRateLimit($key);

        \DB::run('UPDATE api_keys SET last_used_at = NOW() WHERE id = ?', [$key['id']]);

        $request->context['api_key'] = $key;

        $next($request);
    }

    private function extractKey(Request $request): ?string
    {
        $bearer = $request->bearerToken();
        if ($bearer !== null) return $bearer;
        return $request->input('api_key');
    }

    private function checkRateLimit(array $key): void
    {
        $keyId  = (int) $key['id'];
        $limit  = (int) ($key['rate_limit'] ?? 60);
        $window = self::WINDOW_SECONDS;

        \DB::run(
            'DELETE FROM api_key_hits WHERE key_id = ? AND hit_at < NOW() - INTERVAL ? SECOND',
            [$keyId, $window]
        );

        $count = (int) (\DB::one(
            'SELECT COUNT(*) AS c FROM api_key_hits WHERE key_id = ? AND hit_at >= NOW() - INTERVAL ? SECOND',
            [$keyId, $window]
        )['c'] ?? 0);

        if ($count >= $limit) {
            $oldest = \DB::one(
                'SELECT hit_at FROM api_key_hits WHERE key_id = ? ORDER BY hit_at ASC LIMIT 1',
                [$keyId]
            );
            $retryAfter = $oldest
                ? max(1, $window - (time() - strtotime($oldest['hit_at'])))
                : $window;

            header('Retry-After: '        . $retryAfter);
            header('X-RateLimit-Limit: '  . $limit);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: '  . (time() + $retryAfter));
            $this->abort(429, "Rate limit atingido. Tente em {$retryAfter}s. Limite: {$limit} req/{$window}s.");
        }

        \DB::exec(
            'INSERT INTO api_key_hits (key_id, hit_at) VALUES (?, NOW(3))',
            [$keyId]
        );

        header('X-RateLimit-Limit: '     . $limit);
        header('X-RateLimit-Remaining: ' . max(0, $limit - $count - 1));
    }

    private function abort(int $status, string $message): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'data'   => null,
            'errors' => [['status' => $status, 'message' => $message]],
            'meta'   => ['status' => $status],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
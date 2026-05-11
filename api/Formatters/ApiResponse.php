<?php

namespace FlexCore\Api\Formatters;

/**
 * ApiResponse — formata e envia respostas JSON da API.
 * Compatible: PHP 7.4+
 *
 * Envelope:
 * { "data": {...}|[...], "meta": {...}|null, "errors": null|[...] }
 */
class ApiResponse
{
    public static function ok($data, array $meta = [], int $status = 200): void
    {
        self::send($status, $data, $meta, null);
    }

    public static function created($data, array $meta = []): void
    {
        self::send(201, $data, $meta, null);
    }

    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }

    /** @param string|array $errors */
    public static function error($errors, int $status = 400): void
    {
        $errors = is_string($errors) ? [$errors] : $errors;
        self::send($status, null, [], $errors);
    }

    public static function notFound(string $message = 'Recurso não encontrado.'): void
    {
        self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Não autorizado.'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Acesso negado.'): void
    {
        self::error($message, 403);
    }

    public static function validationError(array $errors): void
    {
        self::send(422, null, [], $errors);
    }

    private static function send(int $status, $data, array $meta, ?array $errors): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Powered-By: FlexCore');

        echo json_encode([
            'data'   => $data,
            'meta'   => empty($meta) ? null : $meta,
            'errors' => $errors,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }
}
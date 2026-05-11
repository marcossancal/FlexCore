<?php

namespace FlexCore\Core\Router;

/**
 * Request — wraps HTTP input.
 * Compatible: PHP 7.4+
 */
final class Request
{
    /** @var string */
    public $method;
    /** @var string */
    public $uri;
    /** @var array */
    public $query;
    /** @var array */
    public $body;
    /** @var array */
    public $server;
    /** @var array */
    public $files;
    /** @var array */
    public $routeParams;
    /** @var array  — usado por middleware para passar dados ao handler */
    public $context = [];

    public function __construct(array $routeParams = [])
    {
        $this->method      = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri         = $_SERVER['REQUEST_URI'] ?? '/';
        $this->query       = $_GET    ?? [];
        $this->body        = $_POST   ?? [];
        $this->server      = $_SERVER ?? [];
        $this->files       = $_FILES  ?? [];
        $this->routeParams = $routeParams;
    }

    /** Valor de POST ou GET. */
    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /** Parâmetro de rota (ex: {id}). */
    public function param(string $key, $default = null)
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isJson(): bool
    {
        return strpos($this->server['CONTENT_TYPE'] ?? '', 'application/json') !== false;
    }

    /** Parse do body JSON (requests de API). */
    public function json(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw ?: '{}', true) ?? [];
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function bearerToken(): ?string
    {
        $header = $this->server['HTTP_AUTHORIZATION'] ?? '';
        if (strpos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }
        return null;
    }
}
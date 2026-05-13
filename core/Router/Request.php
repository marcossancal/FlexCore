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
    /** @var array  — Used by middleware to send data to handler */
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

    /** POST or GET Values. */
    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /** Route parameters (ex: {id}). */
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

    /** JSON body Parse (API requests). */
    public function json(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw ?: '{}', true) ?? [];
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Extracts Bearer token from Authorization header.
     *
     * Apache/XAMPP in some modes (CGI, FastCGI) doesnt fill
     * HTTP_AUTHORIZATION automatically. 'Try order':
     *   1. $_SERVER['HTTP_AUTHORIZATION']        — default mod_php 
     *   2. $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] — after RewriteRule
     *   3. getallheaders()['Authorization']      — Apache fallback 
     *   4. apache_request_headers()              — getallheaders() alias
     */
    public function bearerToken(): ?string
    {
        $header = $this->resolveAuthorizationHeader();
        if ($header !== null && strpos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }
        return null;
    }

    private function resolveAuthorizationHeader(): ?string
    {
        // 1. Caminho normal (mod_php ou .htaccess com E=HTTP_AUTHORIZATION)
        if (!empty($this->server['HTTP_AUTHORIZATION'])) {
            return $this->server['HTTP_AUTHORIZATION'];
        }

        // 2. Apache rewrite sets as REDIRECT_HTTP_AUTHORIZATION
        if (!empty($this->server['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $this->server['REDIRECT_HTTP_AUTHORIZATION'];
        }

        // 3. getallheaders() — avaliable in mod_php and some CGI configs
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            // Search case-insensitive
            foreach ($headers as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    return $value;
                }
            }
        }

        return null;
    }
}
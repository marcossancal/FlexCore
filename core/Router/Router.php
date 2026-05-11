<?php

namespace FlexCore\Core\Router;

/**
 * Router — resolve URI → handler.
 * Compatible: PHP 7.4+
 */
class Router
{
    /** @var Route[] */
    private $routes = [];

    /** @var string */
    private $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = $basePath;
    }

    // ── Registro de rotas ─────────────────────────────────────────────

    /** @param callable|array $handler */
    public function get(string $pattern, $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    /** @param callable|array $handler */
    public function post(string $pattern, $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    /** @param callable|array $handler */
    public function any(string $pattern, $handler): void
    {
        $this->add('ANY', $pattern, $handler);
    }

    /** @param callable|array $handler */
    private function add(string $method, string $pattern, $handler): void
    {
        $this->routes[] = new Route($method, $pattern, $handler);
    }

    // ── Dispatch ──────────────────────────────────────────────────────

    public function dispatch(): void
    {
        $uri    = $this->resolveUri();
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        foreach ($this->routes as $route) {
            if ($route->matches($method, $uri, $params)) {
                $route->call($params);
                return;
            }
        }

        http_response_code(404);
        if (file_exists(BASE . '/app/views/errors/404.php')) {
            include BASE . '/app/views/errors/404.php';
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function resolveUri(): string
    {
        $uri = strtok(rawurldecode($_SERVER['REQUEST_URI'] ?? '/'), '?');
        $uri = '/' . trim((string) $uri, '/');

        if ($this->basePath !== '' && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        return ($uri === '' || $uri === false) ? '/' : $uri;
    }
}
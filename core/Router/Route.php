<?php

namespace FlexCore\Core\Router;

/**
 * Route — value object imutável.
 * Compatible: PHP 7.4+
 */
final class Route
{
    /** @var string */
    private $method;
    /** @var string */
    private $pattern;
    /** @var callable|array */
    private $handler;
    /** @var string */
    private $regex;
    /** @var array */
    private $paramNames = [];

    /**
     * @param callable|array $handler
     */
    public function __construct(string $method, string $pattern, $handler)
    {
        $this->method  = $method;
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->compile();
    }

    public function matches(string $method, string $uri, ?array &$params = null): bool
    {
        if ($this->method !== 'ANY' && $this->method !== $method) {
            return false;
        }

        if (!preg_match($this->regex, $uri, $m)) {
            return false;
        }

        $params = [];
        foreach ($this->paramNames as $name) {
            $params[$name] = $m[$name] ?? '';
        }

        return true;
    }

    public function call(array $params = []): void
    {
        $handler = $this->handler;

        // [ClassName, 'metodo'] → instancia a classe automaticamente
        if (is_array($handler) && is_string($handler[0])) {
            $handler = [new $handler[0](), $handler[1]];
        }

        call_user_func_array($handler, array_values($params));
    }

    private function compile(): void
    {
        preg_match_all('/\{(\w+)\}/', $this->pattern, $m);
        $this->paramNames = $m[1];

        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $this->pattern);
        $this->regex = '#^' . $regex . '$#';
    }
}
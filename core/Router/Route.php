<?php

namespace FlexCore\Core\Router;

/**
 * Route — value object immutable.
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
    /** @var MiddlewareInterface[] */
    private $middlewares = [];

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

    /** Chain one or more middlewares in this route. */
    public function middleware(MiddlewareInterface ...$mw): self
    {
        foreach ($mw as $m) {
            $this->middlewares[] = $m;
        }
        return $this;
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

        if (empty($this->middlewares)) {
            call_user_func_array($handler, array_values($params));
            return;
        }

        // Chains middlewares: each one calls $next to pass it on.
        // The Request loads the route parameters and becomes available as
        // $request->context['api_key'] after the ApiAuthMiddleware.
        
        $request = new Request($params);

        $chain = array_reduce(
            array_reverse($this->middlewares),
            function (callable $next, MiddlewareInterface $mw) use ($request): callable {
                return function () use ($mw, $request, $next): void {
                    $mw->handle($request, $next);
                };
            },
            function () use ($handler, $request, $params): void {
                // Injeta o Request resolvido pelo middleware no contexto global
                // para que controllers de API possam ler $request->context.
                $GLOBALS['_flexcore_request'] = $request;
                call_user_func_array($handler, array_values($params));
            }
        );

        $chain();
    }

    private function compile(): void
    {
        preg_match_all('/\{(\w+)\}/', $this->pattern, $m);
        $this->paramNames = $m[1];

        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $this->pattern);
        $this->regex = '#^' . $regex . '$#';
    }
}
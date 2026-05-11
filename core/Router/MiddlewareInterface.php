<?php

declare(strict_types=1);

namespace FlexCore\Core\Router;

/**
 * MiddlewareInterface — OCP/LSP: any middleware is interchangeable.
 *
 * A middleware receives the request context and a $next callable.
 * Calling $next() passes control to the next middleware or the handler.
 */
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): void;
}

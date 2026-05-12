<?php

namespace FlexCore\Core\Hooks;

/**
 * Hooks — alias estático de conveniência para HookDispatcher.
 *
 * Mantém a API original (Hooks::on, Hooks::fire, Hooks::applyFilter)
 * redirecionando para os métodos estáticos do HookDispatcher.
 * Plugins existentes continuam funcionando sem alteração.
 *
 * Compatible: PHP 7.4+
 */
class Hooks
{
    public static function on(string $event, callable $listener, int $priority = 10): void
    {
        HookDispatcher::onStatic($event, $listener, $priority);
    }

    public static function fire(string $event, array $args = []): void
    {
        HookDispatcher::fireStatic($event, $args);
    }

    public static function filter(string $event, callable $transformer, int $priority = 10): void
    {
        HookDispatcher::filterStatic($event, $transformer, $priority);
    }

    public static function applyFilter(string $event, mixed $value, array $extraArgs = []): mixed
    {
        return HookDispatcher::applyFilterStatic($event, $value, $extraArgs);
    }

    public static function hasListeners(string $event): bool
    {
        return HookDispatcher::hasListenersStatic($event);
    }

    public static function reset(): void
    {
        HookDispatcher::resetStatic();
    }
}

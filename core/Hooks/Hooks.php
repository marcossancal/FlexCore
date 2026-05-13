<?php

namespace FlexCore\Core\Hooks;

/**
 * Hooks — static convenience alias for HookDispatcher.
 *
 * Preserves the original API (Hooks::on, Hooks::fire, Hooks::applyFilter)
 * by redirecting calls to the static methods of HookDispatcher.
 * Existing plugins continue to work without any changes.
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

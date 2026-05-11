<?php

namespace FlexCore\Core\Hooks;

/**
 * HookDispatcher — sistema de eventos do FlexCore.
 * Compatible: PHP 7.4+
 *
 * Actions  → fire and forget (vários listeners, sem retorno)
 * Filters  → transforma um valor (cada listener recebe e retorna)
 */
class HookDispatcher
{
    /** @var array<string, array<int, callable[]>> */
    private static $actions = [];

    /** @var array<string, array<int, callable[]>> */
    private static $filters = [];

    // ── Actions ───────────────────────────────────────────────────────

    public static function on(string $event, callable $listener, int $priority = 10): void
    {
        self::$actions[$event][$priority][] = $listener;
    }

    public static function fire(string $event, array $args = []): void
    {
        if (empty(self::$actions[$event])) return;
        ksort(self::$actions[$event]);
        foreach (self::$actions[$event] as $listeners) {
            foreach ($listeners as $listener) {
                call_user_func_array($listener, $args);
            }
        }
    }

    // ── Filters ───────────────────────────────────────────────────────

    public static function filter(string $event, callable $transformer, int $priority = 10): void
    {
        self::$filters[$event][$priority][] = $transformer;
    }

    public static function applyFilter(string $event, $value, array $extraArgs = [])
    {
        if (empty(self::$filters[$event])) return $value;
        ksort(self::$filters[$event]);
        foreach (self::$filters[$event] as $transformers) {
            foreach ($transformers as $transformer) {
                $value = call_user_func_array($transformer, array_merge([$value], $extraArgs));
            }
        }
        return $value;
    }

    // ── Inspeção ──────────────────────────────────────────────────────

    public static function hasListeners(string $event): bool
    {
        return !empty(self::$actions[$event]) || !empty(self::$filters[$event]);
    }

    public static function reset(): void
    {
        self::$actions = [];
        self::$filters = [];
    }
}

/** Alias curto. */
class Hooks extends HookDispatcher {}
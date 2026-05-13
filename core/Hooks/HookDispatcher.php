<?php

namespace FlexCore\Core\Hooks;

/**
 * HookDispatcher — FlexCore event system.
 * Compatible: PHP 7.4+
 *
 * Supports two usage modes:
 *
 *   1. Static (legacy, compatible with existing plugins):
 *      Hooks::on('record.created', $fn);
 *      Hooks::fire('record.created', [$id]);
 *
 *   2. Instance-based (injectable, testable):
 *      $hooks = new HookDispatcher();
 *      $hooks->on('record.created', $fn);
 *      $hooks->fire('record.created', [$id]);
 *
 * Instance mode uses its own isolated internal state — ideal
 * for unit testing (without contamination between test cases).
 * Static mode keeps a shared global state (original behavior).
 *
 * Actions  → fire and forget (multiple listeners, no return value)
 * Filters  → transforms a value (each listener receives and returns it)
 */

class HookDispatcher
{
    // ── static state (legacy mode / global) ─────────────────────

    /** @var array<string, array<int, callable[]>> */
    private static array $staticActions = [];

    /** @var array<string, array<int, callable[]>> */
    private static array $staticFilters = [];

    // ── Instance state (injectable mode) ───────────────────────

    /** @var array<string, array<int, callable[]>> */
    private array $actions = [];

    /** @var array<string, array<int, callable[]>> */
    private array $filters = [];

    // ── Actions ───────────────────────────────────────────────────────

    /**
     * Register a listener to an evento (action).
     * When called statically, it runs in global state;
     * When called in a instance, it runs in local state.
     */
    public function on(string $event, callable $listener, int $priority = 10): void
    {
        $this->actions[$event][$priority][] = $listener;
    }

    public static function onStatic(string $event, callable $listener, int $priority = 10): void
    {
        self::$staticActions[$event][$priority][] = $listener;
    }

    public function fire(string $event, array $args = []): void
    {
        if (empty($this->actions[$event])) return;
        ksort($this->actions[$event]);
        foreach ($this->actions[$event] as $listeners) {
            foreach ($listeners as $listener) {
                call_user_func_array($listener, $args);
            }
        }
    }

    public static function fireStatic(string $event, array $args = []): void
    {
        if (empty(self::$staticActions[$event])) return;
        ksort(self::$staticActions[$event]);
        foreach (self::$staticActions[$event] as $listeners) {
            foreach ($listeners as $listener) {
                call_user_func_array($listener, $args);
            }
        }
    }

    // ── Filters ───────────────────────────────────────────────────────

    public function filter(string $event, callable $transformer, int $priority = 10): void
    {
        $this->filters[$event][$priority][] = $transformer;
    }

    public static function filterStatic(string $event, callable $transformer, int $priority = 10): void
    {
        self::$staticFilters[$event][$priority][] = $transformer;
    }

    public function applyFilter(string $event, mixed $value, array $extraArgs = []): mixed
    {
        if (empty($this->filters[$event])) return $value;
        ksort($this->filters[$event]);
        foreach ($this->filters[$event] as $transformers) {
            foreach ($transformers as $transformer) {
                $value = call_user_func_array($transformer, array_merge([$value], $extraArgs));
            }
        }
        return $value;
    }

    public static function applyFilterStatic(string $event, mixed $value, array $extraArgs = []): mixed
    {
        if (empty(self::$staticFilters[$event])) return $value;
        ksort(self::$staticFilters[$event]);
        foreach (self::$staticFilters[$event] as $transformers) {
            foreach ($transformers as $transformer) {
                $value = call_user_func_array($transformer, array_merge([$value], $extraArgs));
            }
        }
        return $value;
    }

    // ── Inspection ──────────────────────────────────────────────────────

    public function hasListeners(string $event): bool
    {
        return !empty($this->actions[$event]) || !empty($this->filters[$event]);
    }

    public static function hasListenersStatic(string $event): bool
    {
        return !empty(self::$staticActions[$event]) || !empty(self::$staticFilters[$event]);
    }

    /** Clean instance state (used in tests) */
    public function reset(): void
    {
        $this->actions = [];
        $this->filters = [];
    }

    /** Clean global static state (used in integrations tests). */
    public static function resetStatic(): void
    {
        self::$staticActions = [];
        self::$staticFilters = [];
    }
}



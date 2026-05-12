<?php

namespace FlexCore\Core\Hooks;

/**
 * HookDispatcher — sistema de eventos do FlexCore.
 * Compatible: PHP 7.4+
 *
 * Suporta dois modos de uso:
 *
 *   1. Estático (legado, compatível com plugins existentes):
 *      Hooks::on('record.created', $fn);
 *      Hooks::fire('record.created', [$id]);
 *
 *   2. Instância (injetável, testável):
 *      $hooks = new HookDispatcher();
 *      $hooks->on('record.created', $fn);
 *      $hooks->fire('record.created', [$id]);
 *
 * O modo instância usa seu próprio estado interno isolado — ideal
 * para testes unitários (sem contaminação entre casos de teste).
 * O modo estático mantém estado global compartilhado (comportamento original).
 *
 * Actions  → fire and forget (vários listeners, sem retorno)
 * Filters  → transforma um valor (cada listener recebe e retorna)
 */
class HookDispatcher
{
    // ── Estado estático (modo legado / global) ─────────────────────

    /** @var array<string, array<int, callable[]>> */
    private static array $staticActions = [];

    /** @var array<string, array<int, callable[]>> */
    private static array $staticFilters = [];

    // ── Estado de instância (modo injetável) ───────────────────────

    /** @var array<string, array<int, callable[]>> */
    private array $actions = [];

    /** @var array<string, array<int, callable[]>> */
    private array $filters = [];

    // ── Actions ───────────────────────────────────────────────────────

    /**
     * Registra um listener para um evento (action).
     * Quando chamado estaticamente opera no estado global;
     * quando chamado em instância opera no estado local.
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

    // ── Inspeção ──────────────────────────────────────────────────────

    public function hasListeners(string $event): bool
    {
        return !empty($this->actions[$event]) || !empty($this->filters[$event]);
    }

    public static function hasListenersStatic(string $event): bool
    {
        return !empty(self::$staticActions[$event]) || !empty(self::$staticFilters[$event]);
    }

    /** Limpa estado da instância (uso em testes). */
    public function reset(): void
    {
        $this->actions = [];
        $this->filters = [];
    }

    /** Limpa estado estático global (uso em testes de integração). */
    public static function resetStatic(): void
    {
        self::$staticActions = [];
        self::$staticFilters = [];
    }
}



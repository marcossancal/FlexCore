<?php

declare(strict_types=1);

namespace FlexCore\Core\Container;

use Closure;
use ReflectionClass;

/**
 * Container — DIP: high-level modules depend on abstractions,
 * not concrete classes. The container resolves those abstractions.
 *
 * Supports:
 *   - Binding interfaces to implementations
 *   - Singleton registration
 *   - Basic autowiring via Reflection
 *
 * Usage:
 *   $c = Container::getInstance();
 *   $c->bind(RepositoryInterface::class, EntityRepository::class);
 *   $c->singleton(DB::class, fn() => new DB($_ENV));
 *
 *   $repo = $c->make(EntityRepository::class);
 */
class Container
{
    private static ?self $instance = null;

    private array $bindings   = [];   // interface → concrete or factory
    private array $singletons = [];   // class → factory
    private array $resolved   = [];   // class → cached instance (singletons only)

    private function __construct() {}

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    // ── Registration ────────────────────────────────────────────────

    /** Bind an interface (or class) to a concrete class or factory. */
    public function bind(string $abstract, string|Closure $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /** Same as bind, but caches the first resolved instance. */
    public function singleton(string $abstract, string|Closure $concrete): void
    {
        $this->singletons[$abstract] = $concrete;
    }

    /** Register an already-built instance as a singleton. */
    public function instance(string $abstract, object $instance): void
    {
        $this->resolved[$abstract] = $instance;
    }

    // ── Resolution ──────────────────────────────────────────────────

    public function make(string $abstract): object
    {
        // Already cached singleton
        if (isset($this->resolved[$abstract])) {
            return $this->resolved[$abstract];
        }

        // Registered singleton → build + cache
        if (isset($this->singletons[$abstract])) {
            $instance = $this->build($this->singletons[$abstract]);
            $this->resolved[$abstract] = $instance;
            return $instance;
        }

        // Registered binding
        if (isset($this->bindings[$abstract])) {
            return $this->build($this->bindings[$abstract]);
        }

        // Attempt autowiring
        return $this->autowire($abstract);
    }

    // ── Internals ───────────────────────────────────────────────────

    private function build(string|Closure $concrete): object
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }
        return $this->autowire($concrete);
    }

    private function autowire(string $class): object
    {
        $ref = new ReflectionClass($class);

        if (!$ref->isInstantiable()) {
            throw new \RuntimeException("Cannot instantiate [{$class}]. Did you forget to bind it?");
        }

        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            return $ref->newInstance();
        }

        $deps = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type && !$type->isBuiltin()) {
                $deps[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $deps[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException(
                    "Cannot resolve parameter [{$param->getName()}] of [{$class}]."
                );
            }
        }

        return $ref->newInstanceArgs($deps);
    }
}

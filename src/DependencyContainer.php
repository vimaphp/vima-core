<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Vima\Core;

use ReflectionClass;
use ReflectionNamedType;
use Closure;
use Psr\Container\ContainerInterface;

/**
 * Class DependencyContainer
 * 
 * A simple PSR-11 compliant dependency injection container with auto-wiring capabilities.
 *
 * @package Vima\Core
 */
class DependencyContainer implements ContainerInterface
{
    private static ?DependencyContainer $instance = null;

    /** @var array<class-string, object|Closure|string> */
    private array $bindings = [];

    /** @var array<class-string, object> */
    private array $instances = [];

    /**
     * @param array $bindings Optional initial bindings.
     */
    public function __construct(array $bindings = [])
    {
        foreach ($bindings as $abstract => $concrete) {
            $this->register($abstract, $concrete);
        }

        if (self::$instance === null) {
            self::$instance = $this;
        }
    }

    /**
     * Singleton accessor for the container.
     *
     * @return DependencyContainer
     */
    public static function getInstance(): DependencyContainer
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a binding between an abstract and a concrete implementation.
     *
     * @param string $abstract
     * @param object|string|null $concrete
     * @return void
     * @throws \RuntimeException
     */
    public function register(string $abstract, object|string|null $concrete = null): void
    {
        unset($this->instances[$abstract]);

        if ($concrete === null && class_exists($abstract)) {
            $this->bindings[$abstract] = fn(self $c) => new $abstract();
            return;
        }

        if (is_string($concrete) && class_exists($concrete)) {
            $this->bindings[$abstract] = fn(self $c) => new $concrete();
            return;
        }

        if ($concrete instanceof Closure) {
            $this->bindings[$abstract] = $concrete;
            return;
        }

        if (is_object($concrete)) {
            $this->instances[$abstract] = $concrete;
            return;
        }

        throw new \RuntimeException("Cannot register binding for {$abstract}");
    }

    /**
     * Register multiple bindings at once.
     *
     * @param array $dependencies
     * @return void
     */
    public function registerMany(array $dependencies): void
    {
        foreach ($dependencies as $abstract => $concrete) {
            if (is_int($abstract)) {
                $this->register($concrete);
            } else {
                $this->register($abstract, $concrete);
            }
        }
    }

    /**
     * Manually bind an instance to an abstract.
     *
     * @param string $abstract
     * @param object $instance
     * @return void
     */
    public function bind(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve and retrieve an instance from the container.
     *
     * @param string $id
     * @return object
     * @throws \RuntimeException
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->bindings[$id])) {
            $binding = $this->bindings[$id];
            $object = $binding instanceof Closure ? $binding($this) : $binding;

            if (!$object instanceof $id && !(interface_exists($id) || (class_exists($id) && (new ReflectionClass($id))->isAbstract()))) {
                throw new \RuntimeException("Resolved binding for {$id} is not an instance of {$id}");
            }

            return $this->instances[$id] = $object;
        }

        // If class or interface does not exist at all
        if (!class_exists($id) && !interface_exists($id)) {
            throw new \RuntimeException("Class or interface {$id} not found.");
        }

        // If it's an interface or abstract class and no binding was registered → fail
        $reflector = new ReflectionClass($id);
        if ($reflector->isInterface() || $reflector->isAbstract()) {
            throw new \RuntimeException("Cannot resolve {$id}, no concrete implementation registered.");
        }

        // Otherwise try to auto-build
        return $this->instances[$id] = $this->build($id);
    }

    /**
     * Check if the container has a binding or instance for the given ID.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id) || interface_exists($id);
    }

    /**
     * Auto-wire and build an instance of the given class.
     *
     * @param string $class
     * @return object
     * @throws \RuntimeException
     */
    private function build(string $class): object
    {
        $reflector = new ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("Class {$class} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class;
        }

        $params = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($param->isDefaultValueAvailable()) {
                    $params[] = $param->getDefaultValue();
                    continue;
                }
                throw new \RuntimeException(
                    "Cannot resolve parameter \${$param->getName()} of {$class}"
                );
            }

            $dependency = $type->getName();
            $params[] = $this->get($dependency);
        }

        return $reflector->newInstanceArgs($params);
    }

    /**
     * Reset the singleton instance (useful for testing).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
        self::getInstance();
    }
}

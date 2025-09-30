<?php
declare(strict_types=1);

namespace Vima\Core;

use ReflectionClass;
use ReflectionNamedType;
use Closure;
use Psr\Container\ContainerInterface;

class DependencyContainer implements ContainerInterface
{
    private static ?DependencyContainer $instance = null;

    /** @var array<class-string, object|Closure|string> */
    private array $bindings = [];

    /** @var array<class-string, object> */
    private array $instances = [];

    public function __construct(array $bindings = [])
    {
        foreach ($bindings as $abstract => $concrete) {
            $this->register($abstract, $concrete);
        }

        if (self::$instance === null) {
            self::$instance = $this;
        }
    }

    public static function getInstance(): DependencyContainer
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(string $abstract, object|string|null $concrete = null): void
    {
        if ($concrete === null && class_exists($abstract)) {
            $this->bindings[$abstract] = fn() => new $abstract();
            return;
        }

        if (is_string($concrete) && class_exists($concrete)) {
            $this->bindings[$abstract] = fn() => new $concrete();
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

    public function bind(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

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

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id) || interface_exists($id);
    }

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

    public static function reset(): void
    {
        self::$instance = null;
        self::getInstance();
    }
}

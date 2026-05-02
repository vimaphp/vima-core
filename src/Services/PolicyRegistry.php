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

namespace Vima\Core\Services;

use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\Contracts\{PolicyInterface, PolicyRegistryInterface};
use Vima\Core\Contracts\CacheInterface;
use Vima\Core\DTOs\AccessContext;
use Vima\Core\Events\DefaultEventDispatcher;
use Vima\Core\Events\Policy\PolicyRegistered;
use Vima\Core\Exceptions\PolicyNotFoundException;
use Vima\Core\Exceptions\PolicyMethodNotFoundException;
use Vima\Core\Support\Utils;
use Vima\Core\Attributes\MapToPermission;
use ReflectionClass;
use function Vima\Core\resolve;

/**
 * Class PolicyRegistry
 * 
 * Central registry for ABAC policies. It maps ability names to callback functions
 * that evaluate context-aware authorization rules.
 *
 * @package Vima\Core\Services
 */
class PolicyRegistry implements PolicyRegistryInterface
{
    /** @var array<string, callable> */
    private array $policies = [];

    /** @var array<string, string> */
    private array $policiesClasses = [];

    /** @var array<string, array<string, string>> Cache for class method mappings */
    private array $methodMappingCache = [];

    private ?EventDispatcherInterface $dispatcher;
    private ?CacheInterface $cache;

    public function __construct(?EventDispatcherInterface $dispatcher = null, ?CacheInterface $cache = null)
    {
        $this->dispatcher = $dispatcher ?? (defined('Vima\Core\Contracts\EventDispatcherInterface') ? \Vima\Core\resolve(EventDispatcherInterface::class) : new DefaultEventDispatcher());
        $this->cache = $cache ?? (defined('Vima\Core\Contracts\CacheInterface') ? \Vima\Core\resolve(CacheInterface::class) : null);
    }

    private static $instance = null;

    /**
     * Singleton accessor for the registry.
     *
     * @return PolicyRegistry
     */
    public static function instance(): PolicyRegistry
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register a callback for a specific ability/action.
     *
     * @param string $ability The name of the permission or action.
     * @param callable $callback Logic to evaluate (User, ...arguments) -> bool.
     * @return void
     */
    public function register(string $ability, callable $callback): void
    {
        $this->policies[$ability] = $callback;
        $this->dispatcher->dispatch(new PolicyRegistered($ability, $callback));
    }

    /**
     * Register a policy class for a resource.
     *
     * @param string $resourceClass The resource class name.
     * @param string $policyClass The policy class name.
     * @return void
     */
    public function registerClass(string $resourceClass, string $policyClass): void
    {
        if (!is_subclass_of($policyClass, PolicyInterface::class)) {
            throw new \InvalidArgumentException("Policy class {$policyClass} must implement Vima\Core\Contracts\PolicyInterface");
        }
        $this->policiesClasses[$resourceClass] = $policyClass;
        $this->dispatcher->dispatch(new PolicyRegistered($resourceClass, $policyClass));
    }

    /**
     * @return array<string, string>
     */
    public function getRegisteredClasses(): array
    {
        return $this->policiesClasses;
    }

    /**
     * Evaluate a registered policy or class method.
     *
     * @param object $user The user object.
     * @param string $ability The ability name (e.g., 'posts.edit' or 'edit').
     * @param mixed ...$arguments Contextual arguments. Usually first is the resource.
     * @return bool|\Vima\Core\DTOs\AccessResponse
     * @throws \Exception If a resource is provided but no policy is registered.
     */
    public function evaluate(object $user, string $ability, ?string $namespace = null, ...$arguments): bool|\Vima\Core\DTOs\AccessResponse
    {
        [$permNamespace, $permName] = Utils::splitPermission($ability);
        $permNamespace ??= $namespace;

        $context = new AccessContext(
            $user,
            $permName,
            resolve(AccessManager::class),
            $permNamespace,
        );

        $resourceArg = $arguments[0] ?? null;

        if (isset($resourceArg)) {
            unset($arguments[0]);
            $arguments = array_values($arguments);
            $context->additionalContext = $arguments;
        }

        $result = null;

        // 1. Try class-based policy if resource is provided
        if ($resourceArg && is_object($resourceArg)) {
            $resourceClass = get_class($resourceArg);
            $policyClass = $this->resolvePolicyClass($resourceClass);

            if ($policyClass) {
                $method = $this->resolveMethodViaAttributes($policyClass, $permName, $permNamespace);

                if (!$method) {
                    $method = $this->resolveMethodName($permName);
                }

                $policy = new $policyClass();
                if (method_exists($policy, $method)) {
                    $result = $policy->$method($context, $resourceArg);
                } else {
                    throw new PolicyMethodNotFoundException($policyClass, $method);
                }
            }
        }

        // 2. Fallback to callback-based policies if no class policy result
        if ($result === null && isset($this->policies[$permName])) {
            $result = call_user_func($this->policies[$permName], $context, $resourceArg);
        }

        if ($result === null) {
            throw new PolicyNotFoundException($ability);
        }

        return $result;
    }

    /**
     * Maps ability to method name: can{CamelCaseAbility}
     * 'posts.edit' -> canEdit
     * 'edit' -> canEdit
     *
     * @param string $ability
     * @return string
     */
    protected function resolveMethodName(string $ability): string
    {
        // Handle 'posts.edit' format
        if (strpos($ability, '.') !== false) {
            $parts = explode('.', $ability);
            unset($parts[0]); // Remove the resource part, keep the action
            $ability = implode('.', $parts); // Reconstruct the ability without the resource
        }

        // Convert to camelCase and prefix with 'allows'
        $method = 'can' . str_replace(' ', '', ucwords(str_replace(['-', '_', '.'], ' ', $ability)));

        return $method;
    }

    /**
     * Resolves a method name using the MapToPermission attribute.
     *
     * @param string $policyClass
     * @param string $permission
     * @param string|null $namespace
     * @return string|null
     */
    protected function resolveMethodViaAttributes(string $policyClass, string $permission, ?string $namespace = null): ?string
    {
        if (!isset($this->methodMappingCache[$policyClass])) {
            $cacheKey = 'vima:policies:' . str_replace('\\', '_', $policyClass) . ':methods';
            $cached = $this->cache ? $this->cache->get($cacheKey) : null;

            if ($cached !== null) {
                $this->methodMappingCache[$policyClass] = $cached;
            } else {
                $this->methodMappingCache[$policyClass] = [];
                $reflection = new ReflectionClass($policyClass);

                foreach ($reflection->getMethods() as $method) {
                    $attributes = $method->getAttributes(MapToPermission::class);
                    foreach ($attributes as $attribute) {
                        /** @var MapToPermission $map */
                        $map = $attribute->newInstance();
                        $key = ($map->namespace ? $map->namespace . ':' : '') . $map->permission;
                        $this->methodMappingCache[$policyClass][$key] = $method->getName();
                    }
                }

                if ($this->cache) {
                    $this->cache->set($cacheKey, $this->methodMappingCache[$policyClass], 3600);
                }
            }
        }

        // Check for namespaced match first
        if ($namespace) {
            $namespacedKey = $namespace . ':' . $permission;
            if (isset($this->methodMappingCache[$policyClass][$namespacedKey])) {
                return $this->methodMappingCache[$policyClass][$namespacedKey];
            }
        }

        // Check for non-namespaced match
        return $this->methodMappingCache[$policyClass][$permission] ?? null;
    }

    /**
     * Helper to mass-register policies.
     *
     * @param array<string, callable> $rules
     * @return self
     */
    public static function define(array $rules): self
    {
        $registry = self::instance();

        foreach ($rules as $ability => $callback) {
            $registry->register($ability, $callback);
        }

        return $registry;
    }

    /**
     * Check if a policy exists for the given action or resource.
     *
     * @param string $ability
     * @param mixed $resource Optional resource to check for class-based policy existence.
     * @return bool
     */
    public function has(string $ability, ?string $resource = null): bool
    {
        if ($resource) {
            $classPolicy = $this->resolvePolicyClass($resource);

            if ($classPolicy) {
                return true;
            }

            // If no then use the ability name to check for callback-based policy as fallback
        }
        return (bool) $this->resolvePolicyCallback($ability);
    }

    private function resolvePolicyClass(string $resourceClass): ?string
    {
        return $this->policiesClasses[$resourceClass] ?? null;
    }

    private function resolvePolicyCallback(string $ability): ?callable
    {
        return $this->policies[$ability] ?? null;
    }
}

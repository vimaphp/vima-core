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

use Vima\Core\Contracts\{PolicyInterface, PolicyRegistryInterface};

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

    private static $instance = null;

    /**
     * Singleton accessor for the registry.
     *
     * @return PolicyRegistry
     */
    public static function instance(): PolicyRegistry
    {
        $instance = self::$instance;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
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
    }

    /**
     * Evaluate a registered policy or class method.
     *
     * @param object $user The user object.
     * @param string $ability The ability name (e.g., 'posts.edit' or 'edit').
     * @param mixed ...$arguments Contextual arguments. Usually first is the resource.
     * @return bool
     * @throws \Exception If a resource is provided but no policy is registered.
     */
    public function evaluate(object $user, string $ability, ...$arguments): bool
    {
        // 1. Try class-based policy if resource is provided
        if (!empty($arguments) && is_object($arguments[0])) {
            $resource = $arguments[0];
            $resourceClass = get_class($resource);

            if (isset($this->policiesClasses[$resourceClass])) {
                $policyClass = $this->policiesClasses[$resourceClass];
                $method = $this->resolveMethodName($ability);

                $policy = new $policyClass();
                if (method_exists($policy, $method)) {
                    return (bool) $policy->$method($user, ...$arguments);
                }
            }

            // User specifically asked to throw exception if resource provided but no policy found
            throw new \Exception("No policy registered for resource class: {$resourceClass}");
        }

        // 2. Fallback to callback-based policies
        if (!isset($this->policies[$ability])) {
            return false;
        }

        return (bool) call_user_func($this->policies[$ability], $user, ...$arguments);
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
            $ability = end($parts);
        }

        // Convert to camelCase and prefix with 'can'
        $method = 'can' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $ability)));

        return $method;
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
     * @param mixed ...$arguments
     * @return bool
     */
    public function has(string $ability, ...$arguments): bool
    {
        if (!empty($arguments) && is_object($arguments[0])) {
            return isset($this->policiesClasses[get_class($arguments[0])]);
        }
        return isset($this->policies[$ability]);
    }
}

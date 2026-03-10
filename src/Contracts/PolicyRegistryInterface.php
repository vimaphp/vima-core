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

namespace Vima\Core\Contracts;

/**
 * Interface PolicyRegistryInterface
 * 
 * Defines the contract for the ABAC policy registry.
 *
 * @package Vima\Core\Contracts
 */
interface PolicyRegistryInterface
{
    /**
     * Register a policy logic for a given ability.
     *
     * @param string $ability
     * @param callable $callback
     * @return void
     */
    public function register(string $ability, callable $callback): void;

    /**
     * Register a class-based policy for a resource.
     *
     * @param string $resourceClass
     * @param string $policyClass
     * @return void
     */
    public function registerClass(string $resourceClass, string $policyClass): void;

    /**
     * Evaluate if a user meets the policy requirements for an ability.
     *
     * @param object $user The user object.
     * @param string $ability The ability name.
     * @param string|null $namespace The namespace of the resource.
     * @param mixed ...$arguments Contextual arguments for evaluation.
     * @return bool
     * @throws \Vima\Core\Exceptions\PolicyNotFoundException
     * @throws \Vima\Core\Exceptions\PolicyMethodNotFoundException
     */
    public function evaluate(object $user, string $ability, ?string $namespace = null, ...$arguments): bool;

    /**
     * Check if a policy exists for the given action.
     *
     * @param string $action
     * @param mixed ...$arguments
     * @return bool
     */
    public function has(string $action, ...$arguments): bool;
}
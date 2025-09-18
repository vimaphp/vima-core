<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Contracts\UserInterface;

interface PolicyRegistryInterface
{
    /**
     * Register a policy for a given action or resource.
     */
    public function register(string $ability, callable $callback): void;

    /**
     * Evaluate if a user can perform an ability on a resource.
     */
    public function evaluate(UserInterface $user, string $ability, mixed $resource): bool;

    /**
     * Evaluate if a user can perform an ability on a resource.
     */
    public function has(string $action): bool;
}
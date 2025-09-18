<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

interface AccessManagerInterface
{
    public function userHasPermission(UserInterface $user, string $permission): bool;

    /**
     * Authorize the given user for a specific permission.
     * If $resource is provided, ABAC evaluation will also be considered.
     * Should throw AccessDeniedException on failure.
     */
    public function authorize(UserInterface $user, string $permission, mixed $resource = null): void;

    /**
     * Evaluate a fine-grained policy (ABAC).
     */
    public function evaluatePolicy(UserInterface $user, string $action, mixed $resource): bool;

    /**
     * Combined check: returns true only if RBAC allows the permission and,
     * when a $resource is provided and a policy exists, ABAC also allows it.
     */
    public function can(UserInterface $user, string $permission, mixed $resource = null): bool;
}


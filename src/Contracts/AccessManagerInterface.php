<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Entities\Permission;
use Vima\Core\Entities\Role;

interface AccessManagerInterface
{
    public function userHasPermission(object $user, string $permission): bool;

    /**
     * Authorize the given user for a specific permission.
     * If $resource is provided, ABAC evaluation will also be considered.
     * Should throw AccessDeniedException on failure.
     */
    public function authorize(object $user, string $permission, ...$arguments): void;

    /**
     * Evaluate a fine-grained policy (ABAC).
     */
    public function evaluatePolicy(object $user, string $action, ...$arguments): bool;

    /**
     * Combined check: returns true only if RBAC allows the permission and,
     * when a $resource is provided and a policy exists, ABAC also allows it.
     */
    public function can(object $user, string $permission, ...$arguments): bool;

    public function addRole(string|Role $role, ?string $description = null): Role;

    public function addPermission(string|Permission $permission, ?string $description = null): Permission;

    public function updateRole(Role $role): Role;

    public function updatePermission(Permission $permission): Permission;

    public function getRole(string $name): ?Role;

    public function getPermission(string $name): ?Permission;

    public function grantRole(object $user, string|Role $role): void;

    public function revokeRole(object $user, string|Role $role): void;

    public function userHasRole(object $user, string|Role $role): bool;

    public function grantPermission(object $user, string|Permission $permission): void;

    public function revokePermission(object $user, string|Permission $permission): void;

    public function getUserRoles(object $user): array;

    public function getUserPermissions(object $user): array;

    public function syncUserGrants(object $user, array $roles, ?array $permissions = null): void;
}


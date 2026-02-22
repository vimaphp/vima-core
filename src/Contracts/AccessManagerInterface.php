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

use Vima\Core\Entities\Permission;
use Vima\Core\Entities\Role;

/**
 * Interface AccessManagerInterface
 * 
 * Defines the contract for the core access control engine.
 * It manages RBAC (roles/permissions) and ABAC (policies).
 *
 * @package Vima\Core\Contracts
 */
interface AccessManagerInterface
{
    /**
     * Checks if a user has a specific permission through their roles or direct assignment.
     *
     * @param object $user The user object to check.
     * @param string $permission The permission name.
     * @return bool
     */
    public function isPermitted(object $user, string $permission): bool;

    /**
     * Authorize the given user for a specific permission.
     * If $arguments are provided, ABAC evaluation will also be considered.
     *
     * @param object $user The user object.
     * @param string $permission The permission name.
     * @param mixed ...$arguments Optional context arguments for policy evaluation.
     * @throws \Vima\Core\Exceptions\AccessDeniedException If authorization fails.
     */
    public function enforce(object $user, string $permission, ...$arguments): void;

    /**
     * Evaluate a fine-grained policy (ABAC) for a given action.
     *
     * @param object $user The user object.
     * @param string $action The policy action name.
     * @param mixed ...$arguments Contextual arguments passed to the policy.
     * @return bool
     */
    public function evaluatePolicy(object $user, string $action, ...$arguments): bool;

    /**
     * Combined access check: returns true if the user has the required permission (RBAC)
     * and, if arguments are provided, satisfies the relevant policy (ABAC).
     *
     * @param object $user The user object.
     * @param string $permission The permission or ability name.
     * @param mixed ...$arguments Optional arguments for policy evaluation.
     * @return bool
     */
    public function can(object $user, string $permission, ...$arguments): bool;

    /**
     * Create or retrieve a role by name.
     *
     * @param string|Role $role The role name or entity.
     * @param string|null $description Optional role description.
     * @return Role
     */
    public function ensureRole(string|Role $role, ?string $description = null): Role;

    /**
     * Create or retrieve a permission by name.
     *
     * @param string|Permission $permission The permission name or entity.
     * @param string|null $description Optional permission description.
     * @return Permission
     */
    public function ensurePermission(string|Permission $permission, ?string $description = null): Permission;

    /**
     * Save an existing role's changes to persistent storage.
     *
     * @param Role $role The role entity to update.
     * @return Role
     */
    public function updateRole(Role $role): Role;

    /**
     * Save an existing permission's changes to persistent storage.
     *
     * @param Permission $permission The permission entity to update.
     * @return Permission
     */
    public function updatePermission(Permission $permission): Permission;

    /**
     * Retrieve a role by its unique name.
     *
     * @param string $name The role name.
     * @return Role|null
     */
    public function getRole(string $name): ?Role;

    /**
     * Retrieve a permission by its unique name.
     *
     * @param string $name The permission name.
     * @return Permission|null
     */
    public function getPermission(string $name): ?Permission;

    /**
     * Grant a role to a user.
     *
     * @param object $user The user object.
     * @param string|Role $role The role name or entity.
     */
    public function assignRole(object $user, string|Role $role): void;

    /**
     * Revoke a role from a user.
     *
     * @param object $user The user object.
     * @param string|Role $role The role name or entity.
     */
    public function detachRole(object $user, string|Role $role): void;

    /**
     * Check if a user has been assigned a specific role.
     *
     * @param object $user The user object.
     * @param string|Role $role The role name or entity.
     * @return bool
     */
    public function hasRole(object $user, string|Role $role): bool;

    /**
     * Grant a direct permission to a user (not through a role).
     *
     * @param object $user The user object.
     * @param string|Permission $permission The permission name or entity.
     */
    public function permit(object $user, string|Permission $permission): void;

    /**
     * Revoke a direct permission from a user.
     *
     * @param object $user The user object.
     * @param string|Permission $permission The permission name or entity.
     */
    public function forbid(object $user, string|Permission $permission): void;

    /**
     * Retrieve all roles assigned to the given user.
     *
     * @param object $user The user object.
     * @return Role[]
     */
    public function getUserRoles(object $user): array;

    /**
     * Retrieve all permissions (direct and role-based) assigned to the user.
     *
     * @param object $user The user object.
     * @return Permission[]
     */
    public function getUserPermissions(object $user): array;

    /**
     * Retrieve ONLY direct/user-specific permissions (not through roles).
     *
     * @param object $user The user object.
     * @return Permission[]
     */
    public function getDirectPermissions(object $user): array;

    public function govern(string $action, callable $callback): void;

    /**
     * Register a class-based policy for a resource.
     *
     * @param string $resourceClass
     * @param string $policyClass
     * @return void
     */
    public function registerPolicy(string $resourceClass, string $policyClass): void;

    /**
     * Synchronize a user's roles and direct permissions.
     *
     * @param object $user The user object.
     * @param Role[] $roles Array of roles to sync to.
     * @param Permission[]|null $permissions Array of specific permissions to sync to.
     */
    public function reconcileAccess(object $user, array $roles, ?array $permissions = null): void;
}


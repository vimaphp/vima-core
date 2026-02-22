<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Vima\Core\Services;

use Vima\Core\Config\Setup;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\AccessManagerInterface;
use Vima\Core\Contracts\PolicyRegistryInterface;
use Vima\Core\Contracts\{
    RoleRepositoryInterface,
    PermissionRepositoryInterface,
    RolePermissionRepositoryInterface,
    UserRoleRepositoryInterface
};
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Entities\UserPermission;
use Vima\Core\Entities\UserRole;
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Exceptions\PermissionNotFoundException;
use Vima\Core\Exceptions\PolicyNotFoundException;
use Vima\Core\Exceptions\PolicyMethodNotFoundException;
use Vima\Core\Exceptions\RoleNotFoundException;
use function Vima\Core\resolve;

/**
 * AccessManager provides a unified interface for managing user access control.
 * 
 * It combines Role-Based Access Control (RBAC) with optional Attribute-Based Access Control (ABAC)
 * through policies. It handles:
 * - Role assignment and revocation
 * - Permission assignment and revocation (both via roles and directly to users)
 * - Policy evaluation for contextual checks
 * - Syncing user grants (roles + permissions) with configuration
 */
class AccessManager implements AccessManagerInterface
{
    private PermissionManager $permissionManager;
    private RoleRepositoryInterface $roles;
    private PermissionRepositoryInterface $permissions;
    private RolePermissionRepositoryInterface $rolePermisisons;
    private UserRoleRepositoryInterface $userRoles;
    private UserPermissionRepositoryInterface $userPermissions;
    private PolicyRegistryInterface $policies;
    private UserResolver $userResolver;
    private RoleManager $roleManager;

    public function __construct()
    {
        $this->roles = resolve(RoleRepositoryInterface::class);
        $this->permissions = resolve(PermissionRepositoryInterface::class);
        $this->rolePermisisons = resolve(RolePermissionRepositoryInterface::class);
        $this->userRoles = resolve(UserRoleRepositoryInterface::class);
        $this->userPermissions = resolve(UserPermissionRepositoryInterface::class);
        $this->policies = resolve(PolicyRegistry::class);
        $this->userResolver = resolve(UserResolver::class);

        $this->roleManager = new RoleManager();
        $this->permissionManager = new PermissionManager();
    }

    /**
     * Checks if a user has a given permission (through roles).
     *
     * @param object $user The user object (resolved to ID internally).
     * @param string|Permission $permission Permission name to check.
     * @return bool True if the user has the permission, false otherwise.
     */
    public function isPermitted(object $user, string|Permission $permission): bool
    {
        $id = $this->userResolver->resolveId($user);
        $permName = is_string($permission) ? $permission : $permission->name;
        $permId = !is_string($permission) ? $permission->id : null;

        $roles = $this->roleManager->getUserRoles($id, true);

        foreach ($roles as $role) {
            foreach ($role->permissions as $perm) {
                if ($permId !== null && $perm->id === $permId) {
                    return true;
                }

                if ($perm->name === $permName) {
                    return true;
                }
            }
        }

        foreach ($this->permissionManager->getDirectPermissions($id) as $perm) {
            if ($permId !== null && $perm->id === $permId) {
                return true;
            }

            if ($perm->name === $permName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the user can perform an action (RBAC + ABAC).
     *
     * @param object|string $user User object or identifier.
     * @param string $permission Permission or ability name.
     * @param mixed ...$arguments Optional arguments for policy evaluation.
     * @return bool True if authorized, false otherwise.
     */
    public function can(object|string $user, string $permission, ...$arguments): bool
    {
        $hasRbac = $this->isPermitted($user, $permission);

        if (empty($arguments)) {
            return $hasRbac;
        }

        try {
            return $this->evaluatePolicy($user, $permission, ...$arguments);
        } catch (PolicyNotFoundException | PolicyMethodNotFoundException $e) {
            return $hasRbac;
        }
    }

    /**
     * Authorize an action or throw an AccessDeniedException.
     *
     * @param object $user The user attempting the action.
     * @param string $permission Permission or ability name.
     * @param mixed ...$arguments Optional arguments for policy evaluation.
     * @throws AccessDeniedException If authorization fails.
     */
    public function enforce(object $user, string $permission, ...$arguments): void
    {
        if (!$this->can($user, $permission, ...$arguments)) {
            throw new AccessDeniedException($permission, $user, $this->userResolver);
        }
    }

    /**
     * Evaluate a registered policy for the given action.
     *
     * @param object $user User instance.
     * @param string $action Policy action name.
     * @param mixed ...$arguments Context arguments passed to policy.
     * @return bool Result of policy evaluation.
     * @throws PolicyNotFoundException If policy for the action is missing.
     * @throws PolicyMethodNotFoundException If the specific method is missing in the policy class.
     */
    public function evaluatePolicy(object $user, string $action, ...$arguments): bool
    {
        if (!$this->validatePolicyAction($action, ...$arguments)) {
            throw new PolicyNotFoundException($action);
        }

        return $this->policies->evaluate($user, $action, ...$arguments);
    }

    /**
     * Create or retrieve a role by name.
     *
     * @param string|Role $role The role name or entity.
     * @param string|null $description Optional description.
     * @return Role
     */
    public function ensureRole(string|Role $role, ?string $description = null): Role
    {
        try {
            $newRole = $this->roleManager->find($role);
        } catch (RoleNotFoundException $e) {
            $newRole = null;
        }

        if (!$newRole) {
            $newRole = $this->roleManager->create($role, $description);
        }

        return $newRole;
    }

    /**
     * Create or retrieve a permission by name.
     *
     * @param string|Permission $permission The permission name or entity.
     * @param string|null $description Optional description.
     * @return Permission
     */
    public function ensurePermission(string|Permission $permission, ?string $description = null): Permission
    {
        try {
            $newPerm = $this->permissionManager->find($permission);
        } catch (PermissionNotFoundException $e) {
            $newPerm = null;
        }

        if (!$newPerm) {
            $newPerm = $this->permissionManager->create($permission, $description);
        }

        return $newPerm;
    }

    /**
     * Persist an updated role.
     *
     * @param Role $role
     * @return Role
     */
    public function updateRole(Role $role): Role
    {
        return $this->roleManager->save($role);
    }

    /**
     * Persist an updated permission.
     *
     * @param Permission $permission
     * @return Permission
     */
    public function updatePermission(Permission $permission): Permission
    {
        return $this->permissionManager->save($permission);
    }

    /**
     * Get a role by name.
     *
     * @param string $name
     * @return Role|null
     */
    public function getRole(string $name): ?Role
    {
        try {
            return $this->roleManager->find($name);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get a permission by name.
     *
     * @param string $name
     * @return Permission|null
     */
    public function getPermission(string $name): ?Permission
    {
        return $this->permissionManager->find($name);
    }

    /**
     * Assigns a role to a user (creates role if missing).
     *
     * @param object $user User object.
     * @param string|Role $role Role name.
     * @return void
     */
    public function assignRole(object $user, string|Role $role): void
    {
        $userId = $this->userResolver->resolveId($user);

        // check for role by name
        try {
            $roleRecord = $this->roleManager->find($role);
        } catch (RoleNotFoundException $e) {
            $roleRecord = null;
        }

        // create role if it does not exist
        if (!$roleRecord) {
            $roleRecord = $this->roleManager->create($role);
        }

        $userRole = UserRole::define($userId, $roleRecord->id);

        $this->userRoles->assign($userRole);
    }

    /**
     * Revokes a role from a user.
     *
     * @param object $user User object.
     * @param string|Role $role Role name.
     * @return void
     */
    public function detachRole(object $user, string|Role $role): void
    {
        $userId = $this->userResolver->resolveId($user);

        // check for role by name
        $roleRecord = $this->roleManager->find($role);

        if (!$roleRecord) {
            $roleRecord = $this->roleManager->create($role);
        }

        $this->userRoles->revoke(UserRole::define($userId, $roleRecord->id));
    }

    /**
     * Check if a user has a specific role.
     *
     * @param object $user User object.
     * @param string|Role $role Role name.
     * @return bool True if user has the role, false otherwise.
     */
    public function hasRole(object $user, string|Role $role): bool
    {
        $id = $this->userResolver->resolveId($user);

        $userRoles = $this->roleManager->getUserRoles($id);

        foreach ($userRoles as $r) {
            $name = is_string($role) ? $role : $role->name;

            if ($r->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Grants a direct permission to a user (not tied to roles).
     * If the permission does not exist one is created
     *
     * @param object $user User object.
     * @param string|Permission $permission Permission name.
     * @return void
     */
    public function permit(object $user, string|Permission $permission): void
    {
        $userId = $this->userResolver->resolveId($user);

        // check for permission by name
        $permissionRecord = $this->permissionManager->find($permission);

        // create permission if it does not exist
        if (!$permissionRecord) {
            $permissionRecord = $this->permissionManager->create($permission);
        }

        $this->userPermissions
            ->add(
                UserPermission::define(
                    user_id: $userId,
                    permission_id: $permissionRecord->id
                )
            );
    }

    /**
     * Revokes a direct permission from a user.
     *
     * @param object $user User object.
     * @param string|Permission $permission Permission name.
     * @return void
     */
    public function forbid(object $user, string|Permission $permission): void
    {
        $userId = $this->userResolver->resolveId($user);

        // check for role by name
        $permissionRecord = $this->permissionManager->find($permission);

        // create role if it does not exist
        if (!$permissionRecord) {
            $permissionRecord = $this->permissionManager->create($permission);
        }

        $this->userPermissions
            ->remove(UserPermission::define(
                user_id: $userId,
                permission_id: $permissionRecord->id
            ));
    }

    /**
     * Get all roles assigned to a user.
     *
     * @param object $user User object.
     * @param bool $resolve Whether to resolve permissions within roles.
     * @return Role[] List of Role entities.
     */
    public function getUserRoles(object $user, bool $resolve = false): array
    {
        return $this->roleManager->getUserRoles($this->userResolver->resolveId($user), $resolve);
    }

    /**
     * Get all permissions assigned to a user (via roles and directly).
     *
     * @param object $user User object.
     * @return Permission[] List of Permission entities.
     */
    public function getUserPermissions(object $user): array
    {
        $id = $this->userResolver->resolveId($user);

        $userRoles = $this->roleManager->getUserRoles($id, true);

        /**
         * @var Permission[]
         */
        $permissions = [];

        foreach ($userRoles as $r) {
            $permissions = array_merge($permissions, $r->permissions);
        }

        $permissions = array_merge(
            $permissions,
            $this->permissionManager->getDirectPermissions($id)
        );

        // ensure they are unique
        $names = array_map(fn($p) => $p->name, $permissions);
        $unique = array_unique($names);

        return array_filter($permissions, fn($p) => in_array($p->name, $unique));
    }
    /**
     * @inheritDoc
     */
    public function getDirectPermissions(object $user): array
    {
        $id = $this->userResolver->resolveId($user);
        return $this->permissionManager->getDirectPermissions($id);
    }

    /**
     * @inheritDoc
     */
    public function govern(string $action, callable $callback): void
    {
        $this->policies->register($action, $callback);
    }

    /**
     * @inheritDoc
     */
    public function registerPolicy(string $resourceClass, string $policyClass): void
    {
        $this->policies->registerClass($resourceClass, $policyClass);
    }

    /**
     * @inheritDoc
     */
    public function reconcileAccess(object $user, array $roles, ?array $permissions = null): void
    {
        $user_id = $this->userResolver->resolveId($user);

        $allPerms = $this->permissions->all();

        $config = new VimaConfig(
            setup: new Setup(
                roles: $roles,
                permissions: $allPerms
            )
        );

        $resolver = new ConfigResolver($config);

        foreach ($resolver->getRoles() as $name => $value) {
            $role = new Role(
                name: $name,
                description: $value["description"],
                permissions: $value["permissions"]
            );

            $newRole = $this->roles->save($role);

            $this->userRoles->assign(UserRole::define($user_id, $newRole->id));
        }

        foreach ($permissions as $p) {
            $perm = $this->permissions->save($p);

            $this->userPermissions->add(UserPermission::define(
                $user_id,
                $perm->id
            ));
        }
    }

    /**
     * Validates if a policy action exists in the registry.
     *
     * @param string $action
     * @return bool
     */
    private function validatePolicyAction(string $action, ...$arguments): bool
    {
        if (!$this->policies || !$this->policies->has($action, ...$arguments)) {
            return false;
        }

        return true;
    }
}

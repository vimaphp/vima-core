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
use Vima\Core\Contracts\CacheInterface;
use Vima\Core\Contracts\AccessManagerInterface;
use Vima\Core\Contracts\PolicyRegistryInterface;
use Vima\Core\Contracts\{
    RoleRepositoryInterface,
    PermissionRepositoryInterface,
    RolePermissionRepositoryInterface,
    UserRoleRepositoryInterface
};
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Contracts\UserDenyRepositoryInterface;
use Vima\Core\Entities\RolePermission;
use Vima\Core\Entities\RoleParent;
use Vima\Core\Entities\UserDeny;
use Vima\Core\Entities\UserRole;
use Vima\Core\Entities\Permission;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\UserPermission;
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Exceptions\PolicyNotFoundException;
use Vima\Core\Exceptions\PolicyMethodNotFoundException;
use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\Events\Access\AuthorizationChecked;
use Vima\Core\Events\Access\AccessDenied;
use Vima\Core\Events\Grant\PermissionGranted;
use Vima\Core\Events\Grant\PermissionRevoked;
use Vima\Core\Events\Grant\RoleAssigned;
use Vima\Core\Events\Grant\RoleDetached;
use Vima\Core\Support\Utils;
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
    private array $runtimeCache = [];

    public function __construct(
        private RoleRepositoryInterface $roles,
        private PermissionRepositoryInterface $permissions,
        private RolePermissionRepositoryInterface $rolePermissions,
        private UserRoleRepositoryInterface $userRoles,
        private UserPermissionRepositoryInterface $userPermissions,
        private PolicyRegistryInterface $policies,
        private UserResolver $userResolver,
        private RoleManager $roleManager,
        private PermissionManager $permissionManager,
        private VimaConfig $config,
        private CacheInterface $cache,
        private UserDenyRepositoryInterface $userDenies,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * Generates a stable cache key for a specific user and permission check.
     *
     * @param string|int $userId
     * @param string $permission
     * @param string|null $namespace
     * @param array $context
     * @return string
     */
    protected function getCacheKey(string|int $userId, string $permission, ?string $namespace = null, array $context = []): string
    {
        $ctxHash = '';
        if (!empty($context)) {
            ksort($context);
            $ctxHash = '_' . md5(json_encode($context));
        }

        $key = $this->config->cachePrefix . 'auth_' . $userId . '_' . ($namespace ?? 'global') . '_' . $permission . $ctxHash;

        // CodeIgniter/PSR-16 reserved: {}()/\@:
        return str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '_', $key);
    }

    /**
     * Clears the entire Vima cache.
     */
    public function clearCache(): void
    {
        $this->runtimeCache = [];
        if ($this->config->cacheEnabled) {
            $this->cache->clear();
        }
    }

    /**
     * Invalidates all cached authorization results for a specific user.
     * Since keys are granular, we might need a way to clear by tags if the cache supports it,
     * but for now, we'll just clear the whole vima cache if the driver is simple.
     * Or better, we can clear the whole vima cache if the driver is simple.
     *
     * @param string|int $userId
     */
    protected function invalidateUserCache(string|int $userId): void
    {
        if (!$this->config->cacheEnabled) {
            return;
        }

        // Ideally we'd only clear this user's keys, but without tagging support in CacheInterface,
        // we'll at least clear the obvious ones or just rely on TTL.
        // For now, let's just clear the whole Vima cache to be safe on grants.
        $this->runtimeCache = [];
        $this->cache->clear();
    }

    /**
     * Checks if a user has a given permission (through roles).
     *
     * @param object $user The user object (resolved to ID internally).
     * @param string|Permission $permission Permission name to check.
     * @return bool True if the user has the permission, false otherwise.
     */
    public function isPermitted(object $user, string $permission, array $context = [], ?string $namespace = null): bool
    {
        if ($this->config->superAdminBypass && $this->isSuperAdmin($user)) {
            return true;
        }

        $id = $this->userResolver->resolveId($user);

        // 1. Check for explicit denial (Deny layer overrides all)
        if ($this->isDenied($user, $permission)) {
            return false;
        }

        [$permName, $namespace] = $this->resolveNamespace($permission, $namespace);

        $permNamespace = $namespace;

        $cacheKey = null;
        if ($this->config->cacheEnabled) {
            $cacheKey = $this->getCacheKey($id, $permName, $permNamespace, $context);

            if (isset($this->runtimeCache[$cacheKey])) {
                return $this->runtimeCache[$cacheKey];
            }

            $cachedValue = $this->cache->get($cacheKey);
            if ($cachedValue !== null) {
                $this->runtimeCache[$cacheKey] = (bool) $cachedValue;
                return (bool) $cachedValue;
            }
        }

        $permId = !is_string($permission) ? $permission->id : null;

        $roles = $this->roleManager->getUserRoles($id, true);

        if (!empty($context)) {
            $roles = array_filter($roles, function ($r) use ($context) {
                foreach ($context as $k => $v) {
                    if (!isset($r->context[$k]) || $r->context[$k] != $v) {
                        return false;
                    }
                }
                return true;
            });
        }

        $result = false;
        foreach ($roles as $role) {
            foreach ($role->getAllPermissions() as $perm) {
                if ($permId !== null && $perm->id === $permId) {
                    $result = true;
                    break 2;
                }

                // Check for exact match or wildcard match
                $match = false;
                if ($perm->name === $permName) {
                    $match = true;
                } elseif (str_ends_with($perm->name, '*') && str_starts_with($permName, rtrim($perm->name, '*'))) {
                    $match = true;
                }

                if ($match && ($permNamespace === null || $perm->namespace === $permNamespace)) {
                    $result = true;
                    break 2;
                }
            }
        }

        if (!$result) {
            foreach ($this->permissionManager->getDirectPermissions($id) as $perm) {
                if ($permId !== null && $perm->id === $permId) {
                    $result = true;
                    break;
                }

                $match = false;
                if ($perm->name === $permName) {
                    $match = true;
                } elseif (str_ends_with($perm->name, '*') && str_starts_with($permName, rtrim($perm->name, '*'))) {
                    $match = true;
                }

                if ($match && ($permNamespace === null || $perm->namespace === $permNamespace)) {
                    $result = true;
                    break;
                }
            }
        }

        if ($cacheKey) {
            $this->runtimeCache[$cacheKey] = $result;
            $this->cache->set($cacheKey, $result, $this->config->cacheTTL);
        }

        return $result;
    }

    /**
     * Checks if the user can perform an action (RBAC + ABAC).
     *
     * @param object $user User object or identifier.
     * @param string $permission Permission or ability name.
     * @param mixed ...$arguments Optional arguments for policy evaluation.
     * @return bool True if authorized, false otherwise.
     */
    public function can(object $user, string $permission, ?string $namespace = null, ...$arguments): bool
    {
        if ($this->config->superAdminBypass && $this->isSuperAdmin($user)) {
            return true;
        }

        [$permission, $namespace] = $this->resolveNamespace($permission, $namespace);

        $hasRbac = $this->isPermitted($user, $permission, namespace: $namespace);
        $result = $hasRbac;

        if (!empty($arguments)) {
            try {
                $result = $this->evaluatePolicy($user, $permission, $namespace, ...$arguments);
            } catch (PolicyNotFoundException | PolicyMethodNotFoundException $e) {
                $result = $hasRbac;
            }
        }

        $this->dispatcher->dispatch(new AuthorizationChecked(
            $user,
            $permission,
            $result,
            $namespace,
            $arguments
        ));

        return $result;
    }

    /**
     * Authorize an action or throw an AccessDeniedException.
     *
     * @param object $user The user attempting the action.
     * @param string $permission Permission or ability name.
     * @param mixed ...$arguments Optional arguments for policy evaluation.
     * @throws AccessDeniedException If authorization fails.
     */
    public function enforce(object $user, string $permission, ?string $namespace = null, ...$arguments): void
    {
        if ($this->config->superAdminBypass && $this->isSuperAdmin($user)) {
            return;
        }
        [$permission, $namespace] = $this->resolveNamespace($permission, $namespace);

        if (!$this->can($user, $permission, $namespace, ...$arguments)) {
            $this->dispatcher->dispatch(new AccessDenied($user, $permission, $namespace, $arguments));
            throw new AccessDeniedException($permission, $user, $this->userResolver);
        }
    }

    /**
     * Evaluate a registered policy for the given action.
     *
     * @param object $user User instance.
     * @param string $action Policy action name.
     * @param string|null $namespace The namespace of the resource.
     * @param mixed ...$arguments Context arguments passed to policy.
     * @return bool Result of policy evaluation.
     * @throws PolicyNotFoundException If policy for the action is missing.
     * @throws PolicyMethodNotFoundException If the specific method is missing in the policy class.
     */
    public function evaluatePolicy(object $user, string $action, ?string $namespace = null, ...$arguments): bool
    {
        [$action, $namespace] = $this->resolveNamespace($action, $namespace);

        if (!$this->validatePolicyAction($action, ...$arguments)) {
            throw new PolicyNotFoundException($action);
        }

        return $this->policies->evaluate($user, $action, $namespace, ...$arguments);
    }

    /**
     * Create or retrieve a role by name.
     *
     * @param string|Role $role The role name or entity.
     * @param string|null $description Optional description.
     * @return Role
     */
    public function ensureRole(string|Role $role, ?string $description = null, ?string $namespace = null): Role
    {
        if (is_string($role)) {
            [$role, $namespace] = $this->resolveNamespace($role, $namespace);
        }

        $newRole = $this->roleManager->find($role, $namespace);

        if (!$newRole) {
            $newRole = $this->roleManager->create($role, $description, $namespace);
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
    public function ensurePermission(string|Permission $permission, ?string $description = null, ?string $namespace = null): Permission
    {
        if (is_string($permission)) {
            [$permission, $namespace] = $this->resolveNamespace($permission, $namespace);
        }

        $newPerm = $this->permissionManager->find($permission, $namespace);

        if (!$newPerm) {
            $newPerm = $this->permissionManager->create($permission, $description, $namespace);
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
     * @inheritDoc
     */
    public function deleteRole(Role $role): void
    {
        $this->roleManager->delete($role);
    }

    /**
     * Get a role by name.
     *
     * @param string $name
     * @param string $namespace
     * @param bool $resolve Whether to resolve permissions and relationships
     * @return Role|null
     */
    public function getRole(string $name, ?string $namespace = null, bool $resolve = false): ?Role
    {
        [$name, $namespace] = $this->resolveNamespace($name, $namespace);

        return $this->roleManager->find($name, $namespace, $resolve);
    }

    public function getRolePermissions(string|Role $role): array
    {
        return $this->roleManager->getRolePermissions($role);
    }

    /**
     * Get a permission by name.
     *
     * @param string $name
     * @return Permission|null
     */
    public function getPermission(string $name, ?string $namespace = null): ?Permission
    {
        [$name, $namespace] = $this->resolveNamespace($name, $namespace);

        return $this->permissionManager->find($name, $namespace);
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

        if ($role instanceof Role && $role->id !== null) {
            $roleRecord = $role;
        } else {
            // check for role by name
            if (is_string($role)) {
                [$role, $roleNamespace] = $this->resolveNamespace($role);
            } else {
                $roleNamespace = $role->namespace;
            }

            $roleRecord = $this->roleManager->find($role, $roleNamespace);

            // create role if it does not exist
            if (!$roleRecord) {
                $roleRecord = $this->roleManager->create($role, null, $roleNamespace);
            }
        }

        $userRole = UserRole::define($userId, $roleRecord->id);

        $this->userRoles->assign($userRole);
        $this->invalidateUserCache($userId);

        $this->dispatcher->dispatch(new RoleAssigned($userId, $roleRecord));
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
        $this->invalidateUserCache($userId);

        $this->dispatcher->dispatch(new RoleDetached($userId, $roleRecord));
    }

    /**
     * Check if a user has a specific role.
     *
     * @param object $user User object.
     * @param string|Role $role Role name.
     * @param array $context optional context filter
     * @return bool True if user has the role, false otherwise.
     */
    public function hasRole(object $user, string|Role $role, array $context = []): bool
    {
        $id = $this->userResolver->resolveId($user);

        $userRoles = $this->roleManager->getUserRoles($id, false);

        foreach ($userRoles as $r) {
            $name = is_string($role) ? $role : $role->name;

            if ($r->name !== $name) {
                continue;
            }

            if (!empty($context)) {
                $matches = true;
                foreach ($context as $k => $v) {
                    if (!isset($r->context[$k]) || $r->context[$k] != $v) {
                        $matches = false;
                        break;
                    }
                }
                if (!$matches) {
                    continue;
                }
            }

            return true;
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
        $this->invalidateUserCache($userId);

        $this->dispatcher->dispatch(new PermissionGranted($userId, $permissionRecord));
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
        $this->invalidateUserCache($userId);

        $this->dispatcher->dispatch(new PermissionRevoked($userId, $permissionRecord));
    }

    /**
     * Explicitly deny a permission to a user.
     *
     * @param object $user User object.
     * @param string|Permission $permission Permission name or entity.
     * @return void
     */
    public function deny(object $user, string|Permission $permission, ?string $reason = null): void
    {
        $userId = $this->userResolver->resolveId($user);
        $permissionRecord = $this->permissionManager->find($permission);

        if (!$permissionRecord) {
            $permissionRecord = $this->permissionManager->create($permission);
        }

        $this->userDenies->add($userId, $permissionRecord->id, $reason);
        $this->invalidateUserCache($userId);
    }

    /**
     * Remove an explicit denial for a user.
     *
     * @param object $user User object.
     * @param string|Permission $permission Permission name or entity.
     * @return void
     */
    public function undeny(object $user, string|Permission $permission): void
    {
        $userId = $this->userResolver->resolveId($user);
        $permissionRecord = $this->permissionManager->find($permission);

        if ($permissionRecord) {
            $this->userDenies->remove($userId, $permissionRecord->id);
            $this->invalidateUserCache($userId);
        }
    }

    /**
     * Check if a user has an explicit denial for a permission.
     *
     * @param object $user User object.
     * @param string|Permission $permission Permission name or entity.
     * @return bool
     */
    public function isDenied(object $user, string|Permission $permission, ?string $namespace = null): bool
    {
        $permissionRecord = null;

        if (is_string($permission)) {
            [$name, $namespace] = $this->resolveNamespace($permission, $namespace);

            $permissionRecord = $this->permissionManager->find($name, $namespace);
        } elseif ($permission instanceof Permission) {
            $permissionRecord = $this->permissionManager->find($permission);
        }

        if (!$permissionRecord) {
            return false;
        }

        $userId = $this->userResolver->resolveId($user);

        if (!$permissionRecord) {
            return false;
        }

        return $this->userDenies->isDenied($userId, $permissionRecord->id);
    }

    /**
     * @inheritDoc
     */
    public function getDeniedPermissions(object $user): array
    {
        $userId = $this->userResolver->resolveId($user);
        return $this->userDenies->getDeniedPermissions($userId);
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
     * @param array $context context
     * @return Permission[] List of Permission entities.
     */
    public function getUserPermissions(object $user, array $context = []): array
    {
        $id = $this->userResolver->resolveId($user);

        $userRoles = $this->roleManager->getUserRoles($id, true);

        if (!empty($context)) {
            $userRoles = array_filter($userRoles, function ($r) use ($context) {
                foreach ($context as $k => $v) {
                    if (!isset($r->context[$k]) || $r->context[$k] != $v) {
                        return false;
                    }
                }
                return true;
            });
        }

        /**
         * @var Permission[]
         */
        $permissions = [];

        foreach ($userRoles as $r) {
            $permissions = array_merge($permissions, $r->getAllPermissions());
        }

        $permissions = array_merge(
            $permissions,
            $this->permissionManager->getDirectPermissions($id)
        );

        // ensure they are unique by namespace and name
        $unique = [];
        $result = [];

        foreach ($permissions as $p) {
            $key = ($p->namespace ?? 'global') . ':' . $p->name;
            if (!isset($unique[$key])) {
                $unique[$key] = true;
                $result[] = $p;
            }
        }

        return $result;
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
    public function getRoles(?string $namespace = null, bool $onlyGlobal = false, bool $resolve = false): array
    {
        return $this->roleManager->all($namespace, $onlyGlobal, $resolve);
    }

    public function getPermissions(?string $namespace = null, ?object $user = null): array
    {
        $permissions = $this->permissionManager->all($namespace);

        if ($user !== null) {
            foreach ($permissions as $p) {
                $p->denied = $this->isDenied($user, $p);
            }
        }

        return $permissions;
    }

    /**
     * Resolves the namespace from a given name (supporting "namespace:name" format).
     *
     * @param string $name
     * @param string|null $namespace
     * @return array [name, namespace]
     */
    private function resolveNamespace(string $name, ?string $namespace = null): array
    {
        [$permNamespace, $name] = Utils::splitPermission($name);

        if ($namespace === null) {
            $namespace = $permNamespace;
        }

        return [$name, $namespace];
    }

    /**
     * @inheritDoc
     */
    public function getAllPermissions(?string $namespace = null, bool $onlyGlobal = false): array
    {
        return $this->permissions->all($namespace, $onlyGlobal);
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

        $this->invalidateUserCache($user_id);
    }

    /**
     * Validates if a policy action exists in the registry.
     *
     * @param string $action
     * @return bool
     */
    /**
     * @param string $action
     * @param mixed ...$arguments
     * @return bool
     */
    private function validatePolicyAction(string $action, ...$arguments): bool
    {
        if (!$this->policies || !$this->policies->has($action, ...$arguments)) {
            return false;
        }

        return true;
    }

    public function updateUserRole(UserRole $userRole): UserRole
    {
        $this->userRoles->assign($userRole);
        $this->invalidateUserCache($userRole->user_id);
        return $userRole;
    }

    public function deleteUserRole(UserRole $userRole): void
    {
        $this->userRoles->revoke($userRole);
        $this->invalidateUserCache($userRole->user_id);
    }

    public function updateUserPermission(UserPermission $userPermission): UserPermission
    {
        $this->userPermissions->add($userPermission);
        $this->invalidateUserCache($userPermission->user_id);
        return $userPermission;
    }

    public function deleteUserPermission(UserPermission $userPermission): void
    {
        $this->userPermissions->remove($userPermission);
        $this->invalidateUserCache($userPermission->user_id);
    }

    public function updateUserDeny(UserDeny $userDeny): UserDeny
    {
        $this->userDenies->add($userDeny->user_id, $userDeny->permission_id, $userDeny->reason);
        $this->invalidateUserCache($userDeny->user_id);
        return $userDeny;
    }

    public function deleteUserDeny(UserDeny $userDeny): void
    {
        $this->userDenies->remove($userDeny->user_id, $userDeny->permission_id);
        $this->invalidateUserCache($userDeny->user_id);
    }

    public function updateRolePermission(RolePermission $rolePermission): RolePermission
    {
        $this->rolePermissions->assign($rolePermission);
        $this->clearCache();
        return $rolePermission;
    }

    public function deleteRolePermission(RolePermission $rolePermission): void
    {
        $this->rolePermissions->revoke($rolePermission);
        $this->clearCache();
    }

    public function updateRoleParent(RoleParent $roleParent): RoleParent
    {
        /** @var RoleParentRepositoryInterface $repo */
        $repo = resolve(RoleParentRepositoryInterface::class);
        $repo->assign($roleParent);
        $this->clearCache();
        return $roleParent;
    }

    public function deleteRoleParent(RoleParent $roleParent): void
    {
        /** @var RoleParentRepositoryInterface $repo */
        $repo = resolve(RoleParentRepositoryInterface::class);
        $repo->remove($roleParent);
        $this->clearCache();
    }

    public function isSuperAdmin(object $user): bool
    {
        $superAdminRole = $this->config->superAdminRole;

        if (!$superAdminRole) {
            return false;
        }

        return $this->hasRole($user, $superAdminRole);
    }
}

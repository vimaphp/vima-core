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
use Vima\Core\Contracts\UserRoleDenyRepositoryInterface;
use Vima\Core\Entities\Bare\BareRolePermission;
use Vima\Core\Entities\Bare\BareRoleParent;
use Vima\Core\Entities\Bare\BareUserDeny;
use Vima\Core\Entities\Bare\BareUserRoleDeny;
use Vima\Core\Entities\Bare\BareUserRole;
use Vima\Core\Entities\Bare\BareUserPermission;
use Vima\Core\Entities\Permission;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\UserDeny;
use Vima\Core\Entities\UserRoleDeny;
use Vima\Core\Entities\UserRole;
use Vima\Core\Entities\UserPermission;
use Vima\Core\Entities\RolePermission;
use Vima\Core\Entities\RoleParent;
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
        private UserRoleDenyRepositoryInterface $userRoleDenies,
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
        if ($this->isSuperAdminAllowed($user)) {
            return true;
        }

        $id = $this->userResolver->resolveId($user);

        // 1. Check for explicit denial (Deny layer overrides all)
        if ($this->isDenied($user, $permission, $namespace)) {
            return false;
        }

        [$permName, $namespace] = $this->resolveNamespace($permission, $namespace);

        $permNamespace = $namespace;

        $compiled = $this->getCompiledPermissions($user, $context);
        $fullName = ($permNamespace ? $permNamespace . ':' : '') . $permName;

        $checkConstraints = function (?array $constraints) use ($context) {
            if (!$constraints)
                return true;
            foreach ($constraints as $key => $val) {
                if (!isset($context[$key]) || $context[$key] != $val)
                    return false;
            }
            return true;
        };

        // If no context, we only allow if there are NO constraints on the permission
        if (empty($context)) {
            if (isset($compiled[$fullName]) && empty($compiled[$fullName])) {
                return true;
            }

            // Check wildcards with no constraints
            foreach ($compiled as $comp => $constraints) {
                if (empty($constraints) && str_ends_with($comp, '*')) {
                    if (str_starts_with($fullName, rtrim($comp, '*')))
                        return true;
                }
            }
        } else {
            // Context provided: check exact match + constraints
            if (isset($compiled[$fullName]) && $checkConstraints($compiled[$fullName])) {
                return true;
            }

            // Check wildcards + constraints
            foreach ($compiled as $comp => $constraints) {
                if (str_ends_with($comp, '*')) {
                    if (str_starts_with($fullName, rtrim($comp, '*')) && $checkConstraints($constraints)) {
                        return true;
                    }
                }
            }
        }

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

        $roles = $this->getUserRoles($user, true);

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
        if ($this->isSuperAdminAllowed($user)) {
            return true;
        }

        [$permission, $namespace] = $this->resolveNamespace($permission, $namespace);

        $hasRbac = $this->isPermitted($user, $permission, namespace: $namespace);
        $result = $hasRbac;
        $reason = null;

        if (!empty($arguments)) {
            try {
                $evalResult = $this->evaluatePolicy($user, $permission, $namespace, ...$arguments);

                if ($evalResult instanceof \Vima\Core\DTOs\AccessResponse) {
                    if ($evalResult->shouldAbstain()) {
                        $result = $hasRbac;
                    } else {
                        $result = $evalResult->isAllowed();
                        $reason = $evalResult->getReason();
                    }
                } else {
                    $result = (bool) $evalResult;
                }
            } catch (PolicyNotFoundException | PolicyMethodNotFoundException $e) {
                $result = $hasRbac;
            }
        }

        $this->dispatcher->dispatch(new AuthorizationChecked(
            $user,
            $permission,
            $result,
            $namespace,
            $arguments,
            $reason
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
        if ($this->isSuperAdminAllowed($user)) {
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
     * @return bool|\Vima\Core\DTOs\AccessResponse Result of policy evaluation.
     * @throws PolicyNotFoundException If policy for the action is missing.
     * @throws PolicyMethodNotFoundException If the specific method is missing in the policy class.
     */
    public function evaluatePolicy(object $user, string $action, ?string $namespace = null, ...$arguments): bool|\Vima\Core\DTOs\AccessResponse
    {
        if ($this->isSuperAdminAllowed($user)) {
            return true;
        }

        [$action, $namespace] = $this->resolveNamespace($action, $namespace);

        if (!$this->validatePolicyAction($action, ...$arguments)) {
            throw new PolicyNotFoundException($action);
        }

        return $this->policies->evaluate($user, $action, $namespace, ...$arguments);
    }

    /**
     * @inheritDoc
     */
    public function canAny(object $user, array $permissions, ...$arguments): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($user, $permission, null, ...$arguments)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function canAll(object $user, array $permissions, ...$arguments): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->can($user, $permission, null, ...$arguments)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function addRole(string|Role $role, array $permissions = [], ?string $description = null, ?string $namespace = null): Role
    {
        return $this->roleManager->create($role, $description, $namespace, $permissions);
    }

    /**
     * @inheritDoc
     */
    public function addPermission(string|Permission $permission, ?string $description = null, ?string $namespace = null): Permission
    {
        return $this->permissionManager->create($permission, $description, $namespace);
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
     * @param array $context optional context
     * @return void
     */
    public function assignRole(object $user, string|Role $role, array $context = []): void
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

        $this->userRoles->assign(new BareUserRole(
            user_id: $userId,
            role_id: $roleRecord->id,
            context: $context
        ));

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

        $userRole = UserRole::define($userId, $roleRecord->id);
        $this->userRoles->revoke(new BareUserRole(
            user_id: $userRole->user_id,
            role_id: $userRole->role_id
        ));
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

        $userRoles = $this->getUserRoles($user, false);

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

        $userPermission = UserPermission::define(
            userId: $userId,
            permissionId: $permissionRecord->id
        );

        $this->userPermissions->add(new BareUserPermission(
            user_id: $userPermission->user_id,
            permission_id: $userPermission->permission_id,
            constraints: $userPermission->constraints
        ));
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

        $userPermission = UserPermission::define($userId, $permissionRecord->id);
        $this->userPermissions->remove(new BareUserPermission(
            user_id: $userPermission->user_id,
            permission_id: $userPermission->permission_id
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
    public function deny(object $user, string|Permission $permission, ?string $reason = null, ?\DateTimeInterface $expiresAt = null): void
    {
        $userId = $this->userResolver->resolveId($user);

        if (is_string($permission)) {
            [$name, $namespace] = $this->resolveNamespace($permission);

            if ($name === '*') {
                // Virtual ID for wildcard
                $pid = ($namespace ? $namespace . ':' : '') . '*';
                $this->userDenies->add($userId, $pid, $reason, $expiresAt);
                $this->invalidateUserCache($userId);
                return;
            }
        }

        $permissionRecord = $this->permissionManager->find($permission);

        if (!$permissionRecord) {
            $permissionRecord = $this->permissionManager->create($permission);
        }

        $this->userDenies->add($userId, $permissionRecord->id, $reason, $expiresAt);
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

        if (is_string($permission)) {
            [$name, $namespace] = $this->resolveNamespace($permission);

            if ($name === '*') {
                $pid = ($namespace ? $namespace . ':' : '') . '*';
                $this->userDenies->remove($userId, $pid);
                $this->invalidateUserCache($userId);
                return;
            }
        }

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
        $permName = '';
        $permNamespace = $namespace;

        if (is_string($permission)) {
            [$permName, $permNamespace] = $this->resolveNamespace($permission, $namespace);
        } elseif ($permission instanceof Permission) {
            $permName = $permission->name;
            $permNamespace = $permission->namespace;
        }

        $denies = $this->getDeniedPermissions($user);

        foreach ($denies as $deny) {
            if ($deny->isExpired()) {
                continue;
            }

            // Check virtual ID first for performance and wildcards
            $denyId = (string) $deny->permission_id;

            // 1. Check for Global Suspension (*)
            if ($denyId === '*') {
                return true;
            }

            // 2. Check for Namespace Wildcard (namespace:*)
            if ($permNamespace && $denyId === $permNamespace . ':*') {
                return true;
            }

            $deniedPerm = $deny->permission ?? $deny->getPermission();
            if (!$deniedPerm) {
                // If it's not a virtual ID and no perm found, might be a legacy exact ID match
                continue;
            }

            // 3. Check for exact match
            if ($deniedPerm->name === $permName && $deniedPerm->namespace === $permNamespace) {
                return true;
            }

            // 4. Check for wildcard in same namespace if stored as entity
            if ($deniedPerm->namespace === $permNamespace && $deniedPerm->name === '*') {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getDeniedPermissions(object $user): array
    {
        $userId = $this->userResolver->resolveId($user);
        $bareDenies = $this->userDenies->getDeniedPermissions($userId);

        return array_map(fn($bare) => new UserDeny(
            user_id: $bare->user_id,
            permission_id: $bare->permission_id,
            namespace: $bare->namespace,
            reason: $bare->reason,
            expires_at: $bare->expires_at,
            id: $bare->id,
            created_at: $bare->created_at
        ), $bareDenies);
    }

    /**
     * @inheritDoc
     */
    public function denyRole(object $user, string|Role $role, ?string $reason = null, ?\DateTimeInterface $expiresAt = null): void
    {
        $userId = $this->userResolver->resolveId($user);
        $roleEntity = $this->roleManager->find($role);

        if ($roleEntity && $roleEntity->id) {
            $this->userRoleDenies->add($userId, $roleEntity->id, $reason, $expiresAt);
            $this->invalidateUserCache($userId);
        }
    }

    /**
     * @inheritDoc
     */
    public function undenyRole(object $user, string|Role $role): void
    {
        $userId = $this->userResolver->resolveId($user);
        $roleEntity = $this->roleManager->find($role);

        if ($roleEntity && $roleEntity->id) {
            $this->userRoleDenies->remove($userId, $roleEntity->id);
            $this->invalidateUserCache($userId);
        }
    }

    /**
     * @inheritDoc
     */
    public function isRoleDenied(object $user, string|Role $role): bool
    {
        $userId = $this->userResolver->resolveId($user);
        $roleEntity = $this->roleManager->find($role);

        if (!$roleEntity || !$roleEntity->id) {
            return false;
        }

        $denies = $this->getDeniedRoles($user);
        foreach ($denies as $deny) {
            if ($deny->role_id == $roleEntity->id && !$deny->isExpired()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getDeniedRoles(object $user): array
    {
        $userId = $this->userResolver->resolveId($user);
        $bareDenies = $this->userRoleDenies->getDeniedRoles($userId);

        return array_map(fn($bare) => new UserRoleDeny(
            user_id: $bare->user_id,
            role_id: $bare->role_id,
            reason: $bare->reason,
            expires_at: $bare->expires_at,
            id: $bare->id,
            created_at: $bare->created_at
        ), $bareDenies);
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
        $roles = $this->roleManager->getUserRoles($this->userResolver->resolveId($user), $resolve);

        return array_values(array_filter($roles, function ($role) use ($user) {
            return !$this->isRoleDenied($user, $role);
        }));
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

        /** @var Role[] $userRoles */
        $userRoles = $this->getUserRoles($user, true);

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
        [$namespace, $name] = Utils::splitPermission($action);
        $this->policies->register($name, $callback);
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

            $newRole = $this->roleManager->save($role);

            $this->userRoles->assign(new BareUserRole(user_id: $user_id, role_id: $newRole->id));
        }

        if ($permissions !== null) {
            foreach ($permissions as $p) {
                $perm = $this->permissionManager->save($p);

                $this->userPermissions->add(new BareUserPermission(
                    user_id: $user_id,
                    permission_id: $perm->id
                ));
            }
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
        $resource = $arguments[0] ?? null;

        if (!$this->policies) {
            return false;
        }

        if ($resource && is_object($resource)) {
            $resourceClass = get_class($resource);
            return $this->policies->has($action, $resourceClass);
        }

        return $this->policies->has($action);
    }

    public function updateUserRole(BareUserRole $userRole): BareUserRole
    {
        $this->userRoles->assign($userRole);
        $this->invalidateUserCache($userRole->user_id);
        return $userRole;
    }

    public function deleteUserRole(BareUserRole $userRole): void
    {
        $this->userRoles->revoke($userRole);
        $this->invalidateUserCache($userRole->user_id);
    }

    public function updateUserPermission(BareUserPermission $userPermission): BareUserPermission
    {
        $this->userPermissions->add($userPermission);
        $this->invalidateUserCache($userPermission->user_id);
        return $userPermission;
    }

    public function deleteUserPermission(BareUserPermission $userPermission): void
    {
        $this->userPermissions->remove($userPermission);
        $this->invalidateUserCache($userPermission->user_id);
    }

    public function updateUserDeny(BareUserDeny $userDeny): BareUserDeny
    {
        $expiresAt = $userDeny->expires_at ? new \DateTime($userDeny->expires_at) : null;
        $this->userDenies->add($userDeny->user_id, $userDeny->permission_id, $userDeny->reason, $expiresAt);
        $this->invalidateUserCache($userDeny->user_id);
        return $userDeny;
    }

    public function deleteUserDeny(BareUserDeny $userDeny): void
    {
        $this->userDenies->remove($userDeny->user_id, $userDeny->permission_id);
        $this->invalidateUserCache($userDeny->user_id);
    }

    public function updateUserRoleDeny(BareUserRoleDeny $userRoleDeny): BareUserRoleDeny
    {
        $expiresAt = $userRoleDeny->expires_at ? new \DateTime($userRoleDeny->expires_at) : null;
        $this->userRoleDenies->add($userRoleDeny->user_id, $userRoleDeny->role_id, $userRoleDeny->reason, $expiresAt);
        $this->invalidateUserCache($userRoleDeny->user_id);
        return $userRoleDeny;
    }

    public function deleteUserRoleDeny(BareUserRoleDeny $userRoleDeny): void
    {
        $this->userRoleDenies->remove($userRoleDeny->user_id, $userRoleDeny->role_id);
        $this->invalidateUserCache($userRoleDeny->user_id);
    }

    public function updateRolePermission(BareRolePermission $rolePermission): BareRolePermission
    {
        $this->rolePermissions->assign($rolePermission);
        $this->clearCache();
        return $rolePermission;
    }

    public function deleteRolePermission(BareRolePermission $rolePermission): void
    {
        $this->rolePermissions->revoke($rolePermission);
        $this->clearCache();
    }

    public function updateRoleParent(BareRoleParent $roleParent): BareRoleParent
    {
        /** @var RoleParentRepositoryInterface $repo */
        $repo = resolve(RoleParentRepositoryInterface::class);
        $repo->assign($roleParent);
        $this->clearCache();
        return $roleParent;
    }

    public function deleteRoleParent(BareRoleParent $roleParent): void
    {
        /** @var RoleParentRepositoryInterface $repo */
        $repo = resolve(RoleParentRepositoryInterface::class);
        $repo->remove($roleParent);
        $this->clearCache();
    }

    public function revokePermission(object $user, string|Permission $permission, ?string $namespace = null): void
    {
        $userId = $this->userResolver->resolveId($user);
        [$permissionName, $namespace] = $this->resolveNamespace($permission, $namespace);

        $permRecord = $this->permissionManager->find($permissionName, $namespace);
        if ($permRecord) {
            $userPerm = UserPermission::define($userId, $permRecord->id);
            $this->userPermissions->remove(new BareUserPermission(
                user_id: $userPerm->user_id,
                permission_id: $userPerm->permission_id
            ));

            $this->invalidateUserCache($userId);
        }
    }

    public function getRoleParents(Role $role): array
    {
        /** @var RoleParentRepositoryInterface $repo */
        $repo = resolve(RoleParentRepositoryInterface::class);
        $bareRole = new \Vima\Core\Entities\Bare\BareRole(id: $role->id, name: $role->name, namespace: $role->namespace);
        return $repo->getParents($bareRole);
    }

    public function isSuperAdmin(object $user): bool
    {
        $superAdminRole = $this->config->superAdminRole;

        if (!$superAdminRole) {
            return false;
        }

        return $this->hasRole($user, $superAdminRole);
    }

    public function isSuperAdminAllowed(object $user): bool
    {
        if ($this->config->superAdminBypass && $this->isSuperAdmin($user)) {
            return true;
        }

        return false;
    }

    public function getConfig(): VimaConfig
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    public function getCompiledPermissions(object $user, array $context = []): array
    {
        $id = $this->userResolver->resolveId($user);

        $ctxHash = '';
        if (!empty($context)) {
            ksort($context);
            $ctxHash = '_' . md5(json_encode($context));
        }

        $cacheKey = $this->config->cachePrefix . 'compiled_grants_' . $id . $ctxHash;

        if ($this->config->cacheEnabled) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $permissions = $this->getUserPermissions($user, $context);
        $compiled = [];
        foreach ($permissions as $p) {
            $name = ($p->namespace ? $p->namespace . ':' : '') . $p->name;
            $compiled[$name] = $p->constraints;
        }

        if ($this->config->cacheEnabled) {
            $this->cache->set($cacheKey, $compiled, $this->config->cacheTTL);
        }

        return $compiled;
    }
}

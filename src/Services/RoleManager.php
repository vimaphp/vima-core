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

use Vima\Core\Contracts\AccessManagerInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
use Vima\Core\Entities\Bare\BareRole;
use Vima\Core\Entities\Bare\BarePermission;
use Vima\Core\Entities\Bare\BareRolePermission;
use Vima\Core\Entities\Bare\BareUserRole;
use Vima\Core\Entities\Bare\BareRoleParent;
use Vima\Core\Events\Repository\RepositoryAction;
use Vima\Core\Exceptions\RoleNotFoundException;
use Vima\Core\Contracts\EventDispatcherInterface;
use function Vima\Core\resolve;

use Vima\Core\Contracts\CacheInterface;

/**
 * Class RoleManager
 * 
 * Internal service for managing Role entities, user-role assignments, and role-permission relations.
 *
 * @package Vima\Core\Services
 */
class RoleManager
{

    public function __construct(
        private RoleRepositoryInterface $roles,
        private PermissionRepositoryInterface $permissions,
        private RoleParentRepositoryInterface $roleParents,
        private UserRoleRepositoryInterface $userRoles,
        private RolePermissionRepositoryInterface $rolePermissions,
        private EventDispatcherInterface $dispatcher,
        private CacheInterface $cache,
        private \Vima\Core\Config\VimaConfig $config,
    ) {
    }

    /**
     * Create and persist a new role.
     *
     * @param string|Role $name Role name or instance.
     * @param string|null $description Optional description.
     * @param array $permissions Array of permission names or entities.
     * @return Role
     */
    public function create(string|Role $name, ?string $description = null, ?string $namespace = null, array $permissions = []): Role
    {
        if ($name instanceof Role) {
            $role = $name;
        } else {
            $role = Role::define(name: $name, namespace: $namespace, description: $description);
        }

        if ($description !== null && !($name instanceof Role)) {
            $role->description = $description;
        }

        foreach ($permissions as $perm) {
            $role->permit($perm instanceof Permission ? $perm : new Permission(name: $perm));
        }

        return $this->save($role);
    }

    /**
     * Find a role by identifier or instance.
     *
     * @param string|Role|BareRole $role Role name, instance or bare entity.
     * @return Role|null
     */
    public function find(int|string|Role|BareRole $role, ?string $namespace = null, bool $resolve = false): ?Role
    {
        $id = null;
        $name = null;
        $roleNamespace = $namespace;

        if (is_int($role)) {
            $id = $role;
        } elseif (is_string($role)) {
            $name = $role;
        } else {
            $id = $role->id;
            $name = $role->name;
            $roleNamespace = $role->namespace;
        }

        $bareRole = $id
            ? $this->roles->findById($id)
            : $this->roles->findByName((string)$name, $roleNamespace);


        if (!$bareRole) {
            return null;
        }

        return $this->resolveRole($bareRole, resolve: $resolve);
    }

    private array $saving = [];

    /**
     * Save/Update a role entity.
     *
     * @param Role $role
     * @return Role
     */
    public function save(Role $role): Role
    {
        $roleKey = ($role->namespace ?? 'global') . ':' . $role->name;
        if (isset($this->saving[$roleKey])) {
            return $role;
        }
        $this->saving[$roleKey] = true;

        try {
            $isCreating = false;

            /** @var AccessManagerInterface $manager */
            $manager = resolve(AccessManagerInterface::class);
            
            $parents = $role->parents;
            $children = $role->children;
            $permissions = $role->permissions;
            $forbidden = $role->getForbidden();

            // Convert to BareRole for repository
            $bareRole = new BareRole(
                id: $role->id,
                name: $role->name,
                namespace: $role->namespace,
                description: $role->description,
                context: $role->context
            );

            $existing = $this->roles->findByName($bareRole->name, $bareRole->namespace);

            if (!$existing) {
                $isCreating = true;
                $bareRole = $this->roles->save($bareRole);
            } else {
                $bareRole->id = $existing->id;
                $bareRole = $this->roles->save($bareRole);
            }
            
            $role->id = $bareRole->id;

            // save these individually
            foreach ($permissions as $perm) {
                $perm = $manager->ensurePermission($perm);
                $this->rolePermissions->assign(new BareRolePermission(role_id: $role->id, permission_id: $perm->id));
            }

            foreach ($forbidden as $perm) {
                $perm = $manager->ensurePermission($perm);
                $this->rolePermissions->revoke(new BareRolePermission(role_id: $role->id, permission_id: $perm->id));
            }

            foreach ($parents as $p) {
                $pEntity = $manager->ensureRole($p);
                $this->roleParents->assign(new BareRoleParent(role_id: $role->id, parent_id: $pEntity->id));
            }

            foreach ($children as $c) {
                $cEntity = $manager->ensureRole($c);
                $this->roleParents->assign(new BareRoleParent(role_id: $cEntity->id, parent_id: $role->id));
            }

            $role = $this->resolveRole($bareRole, resolve: true);

            if ($isCreating) {
                $this->dispatcher->dispatch(new RepositoryAction(RepositoryAction::ACTION_CREATED, Role::class, $role));
            } else {
                $this->dispatcher->dispatch(new RepositoryAction(RepositoryAction::ACTION_UPDATED, Role::class, $role));
            }

            $this->clearCache();

            return $role;
        } finally {
            unset($this->saving[$roleKey]);
        }
    }

    /**
     * Delete a role from storage.
     *
     * @param Role $role
     * @return void
     */
    public function delete(Role $role): void
    {
        $bareRole = new BareRole(
            id: $role->id,
            name: $role->name,
            namespace: $role->namespace,
            description: $role->description,
            context: $role->context
        );
        $this->roles->delete($bareRole);
        $this->clearCache();
    }

    /**
     * Assign a role to a user.
     *
     * @param string|int $user_id
     * @param string|Role $role
     * @param array $context
     * @param string|null $namespace
     * @return void
     */
    public function assignToUser(string|int $user_id, string|Role $role, array $context = [], ?string $namespace = null): void
    {
        $roleEntity = $this->find($role, namespace: $namespace);
        if ($roleEntity) {
            $this->userRoles->assign(new BareUserRole(user_id: $user_id, role_id: $roleEntity->id, context: $context));
        }
    }

    /**
     * Remove a role from a user.
     *
     * @param string|int $user_id
     * @param string|Role $role
     * @param string|null $namespace
     * @return void
     */
    public function removeFromUser(string|int $user_id, string|Role $role, ?string $namespace = null): void
    {
        $roleEntity = $this->find($role, namespace: $namespace);
        if ($roleEntity) {
            $this->userRoles->revoke(new BareUserRole(user_id: $user_id, role_id: $roleEntity->id));
        }
    }

    /**
     * Get all roles assigned to a user.
     *
     * @param string|int $user_id
     * @param bool $resolve Whether to resolve permissions within roles.
     * @return Role[]
     */
    public function getUserRoles(string|int $user_id, bool $resolve = false): array
    {
        $userRoles = $this->userRoles->getRolesForUser($user_id);
        
        $roles = [];
        foreach ($userRoles as $ur) {
            $bareRole = $this->roles->findById($ur->role_id);
            if ($bareRole) {
                $role = $this->resolveRole($bareRole, resolve: $resolve);
                if ($role) {
                    $role->context = array_merge($role->context ?? [], $ur->context ?? []);
                    $roles[] = $role;
                }
            }
        }

        return $roles;
    }

    /**
     * Resolves a role and loads its permissions and inheritance.
     *
     * @param string|Role|BareRole $role
     * @param bool $isId If true, strings are treated as IDs.
     * @param string|null $namespace
     * @param bool $resolve Whether to resolve relations.
     * @param array $visited Array of visited role IDs to prevent recursion.
     * @return Role|null
     */
    public function resolveRole(int|string|Role|BareRole $role, bool $isId = false, ?string $namespace = null, bool $resolve = false, array $visited = []): ?Role
    {
        if ($role instanceof Role) {
            $bareRole = new BareRole(
                id: $role->id,
                name: $role->name,
                namespace: $role->namespace,
                description: $role->description,
                context: $role->context
            );
        } elseif ($role instanceof BareRole) {
            $bareRole = $role;
        } else {
            $bareRole = $isId ? $this->roles->findById($role) : $this->roles->findByName($role, $namespace);
        }

        if (!$bareRole) {
            return null;
        }

        $roleEntity = new Role(
            name: $bareRole->name,
            namespace: $bareRole->namespace,
            description: $bareRole->description,
            context: $bareRole->context ?? [],
            id: $bareRole->id
        );

        if (!$resolve) {
            return $roleEntity;
        }

        if (in_array($bareRole->id, $visited)) {
            return $roleEntity;
        }
        $visited[] = $bareRole->id;

        /** @var RolePermissionRepositoryInterface $rpRepo */
        $rpRepo = resolve(RolePermissionRepositoryInterface::class);
        /** @var PermissionRepositoryInterface $pmRepo */
        $pmRepo = resolve(PermissionRepositoryInterface::class);

        $rps = $rpRepo->getRolePermissions($bareRole);

        foreach ($rps as $rp) {
            if (isset($rp->permission_id)) {
                $barePerm = $pmRepo->findById($rp->permission_id);
                if ($barePerm) {
                    $p = new Permission(
                        name: $barePerm->name,
                        namespace: $barePerm->namespace,
                        description: $barePerm->description,
                        id: $barePerm->id,
                        constraints: $rp->constraints ?? []
                    );
                    $roleEntity->permissions[] = $p;
                }
            }
        }

        $parents = $this->roleParents->getParents($bareRole);
        foreach ($parents as $parentLink) {
            $parentBare = $this->roles->findById($parentLink->parent_id);
            if ($parentBare) {
                $roleEntity->parents[] = $this->resolveRole($parentBare, resolve: true, visited: $visited);
            }
        }

        $children = $this->roleParents->getChildren($bareRole);
        foreach ($children as $childLink) {
            $childBare = $this->roles->findById($childLink->role_id);
            if ($childBare) {
                $roleEntity->children[] = $this->resolveRole($childBare, resolve: true, visited: $visited);
            }
        }

        return $roleEntity;
    }

    /**
     * Checks if a user has a specific role.
     *
     * @param string|int $user_id
     * @param string|Role $role
     * @param string|null $namespace
     * @return bool
     */
    public function userHasRole(string|int $user_id, string|Role $role, ?string $namespace = null): bool
    {
        $roleEntity = $this->find($role, namespace: $namespace);
        if (!$roleEntity) {
            return false;
        }

        $userRoles = $this->userRoles->getRolesForUser($user_id);
        foreach ($userRoles as $ur) {
            if ($ur->role_id == $roleEntity->id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all roles.
     *
     * @param string|null $namespace
     * @param bool $onlyGlobal
     * @param bool $resolve
     * @return Role[]
     */
    public function all(?string $namespace = null, bool $onlyGlobal = false, bool $resolve = false): array
    {
        $bareRoles = $this->roles->all($namespace);

        if ($onlyGlobal) {
            $bareRoles = array_filter($bareRoles, fn($r) => empty($r->namespace));
        }

        return array_map(fn($r) => $this->resolveRole($r, resolve: $resolve), array_values($bareRoles));
    }

    public function findByName(string $name, ?string $namespace = null): ?Role
    {
        return $this->find($name, $namespace);
    }

    public function deleteAll(): void
    {
        $this->roles->deleteAll();
    }

    /**
     * @param string|Role $role
     * @param array $visited Array of visited role names to prevent circular inheritance loops.
     * @return \Vima\Core\Entities\Permission[]
     */
    public function getRolePermissions(string|Role $role, array &$visited = []): array
    {
        $roleEntity = $this->resolveRole($role, resolve: true);
        if (!$roleEntity) {
            return [];
        }

        $roleKey = ($roleEntity->namespace ?? 'global') . ':' . $roleEntity->name;
        if (in_array($roleKey, $visited)) {
            return [];
        }
        $visited[] = $roleKey;

        $cacheKey = 'vima:roles:' . $roleEntity->id . ':permissions';
        
        if ($this->config->cacheEnabled) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $perms = $roleEntity->permissions;

        foreach ($roleEntity->parents as $parent) {
            $perms = array_merge($perms, $this->getRolePermissions($parent, $visited));
        }

        // De-duplicate by full name
        $uniquePerms = [];
        foreach ($perms as $p) {
            $uniquePerms[$p->getFullName()] = $p;
        }
        $perms = array_values($uniquePerms);

        if ($this->config->cacheEnabled) {
            $this->cache->set($cacheKey, $perms, 3600);
        }

        return $perms;
    }

    public function clearCache(): void
    {
        $this->cache->clear();
    }
}

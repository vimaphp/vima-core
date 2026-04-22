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
use Vima\Core\Entities\RoleParent;
use Vima\Core\Entities\RolePermission;
use Vima\Core\Entities\UserRole;
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
    ) {
    }

    /**
     * Create and persist a new role.
     *
     * @param string|Role $name Role name or instance.
     * @param string|null $description Optional description.
     * @return Role
     */
    public function create(string|Role $name, ?string $description = null, ?string $namespace = null): Role
    {
        $role = $name instanceof Role
            ? $name
            : new Role(name: $name, namespace: $namespace);

        if ($description !== null) {
            $role->description = $description;
        }

        return $this->save($role);
    }

    /**
     * Find a role by identifier or instance.
     *
     * @param string|Role $role Role name or instance.
     * @return Role|null
     */
    public function find(string|Role $role, ?string $namespace = null, bool $resolve = false): ?Role
    {
        $name = is_string($role) ? $role : $role->name;
        $id = !is_string($role) ? $role->id : null;
        $roleNamespace = !is_string($role) ? $role->namespace : $namespace;

        $role = $id
            ? $this->roles->findById($id)
            : $this->roles->findByName($name, $roleNamespace);

        if ($resolve && $role) {
            $role = $this->resolveRole($role);
        }

        return $role;
    }

    /**
     * Save/Update a role entity.
     *
     * @param Role $role
     * @return Role
     */
    public function save(Role $role): Role
    {
        $isCreating = false;

        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        // remove relationships to parents and children before saving to avoid duplicates
        $parents = $role->parents;
        $children = $role->children;
        $permissions = $role->permissions;

        $role->parents = [];
        $role->children = [];
        $role->permissions = [];


        // save role
        $existing = $this->roles->findByName($role->name, $role->namespace);

        if (!$existing) {
            $isCreating = true;
            $role = $this->roles->save($role);
        } else {
            $role->id = $existing->id;
        }

        // save these individually to give less responsibility to framemwork implementations as much as possible and also for better control over events and caching
        foreach ($permissions as $perm) {
            $perm = $manager->ensurePermission($perm);

            $this->rolePermissions->assign(RolePermission::define($role->id, $perm->id));
        }

        foreach ($parents as $p) {
            $p = $manager->ensureRole($p);

            $this->roleParents->assign(RoleParent::define($role->id, $p->id));
        }

        foreach ($children as $c) {
            $c = $manager->ensureRole($c);

            $this->roleParents->assign(RoleParent::define($c->id, $role->id));
        }

        $role = $this->resolveRole($role);

        // dd($role);

        if ($isCreating) {
            $this->dispatcher->dispatch(new RepositoryAction(RepositoryAction::ACTION_CREATED, Role::class, $role));
        } else {
            $this->dispatcher->dispatch(new RepositoryAction(RepositoryAction::ACTION_UPDATED, Role::class, $role));
        }

        return $role;
    }

    /**
     * Delete a role from storage.
     *
     * @param Role $role
     * @return void
     */
    public function delete(Role $role): void
    {
        $this->roles->delete($role);
    }

    /**
     * Assign a role to a user.
     *
     * @param string|int $user_id
     * @param string|Role $role
     * @param array $context
     * @return void
     */
    public function assignToUser(string|int $user_id, string|Role $role, array $context = [], ?string $namespace = null): void
    {
        $roleEntity = $this->resolveRole($role, namespace: $namespace);
        $this->userRoles->assign(UserRole::define($user_id, $roleEntity->id, $context));
    }

    /**
     * Remove a role from a user.
     *
     * @param string|int $user_id
     * @param string|Role $role
     * @return void
     */
    public function removeFromUser(string|int $user_id, string|Role $role, ?string $namespace = null): void
    {
        $roleEntity = $this->resolveRole($role, namespace: $namespace);
        $this->userRoles->revoke(UserRole::define($user_id, $roleEntity->id));
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
        return array_filter($this->userRoles->getRolesForUser($user_id, $resolve));
    }

    /**
     * Resolves a role from various inputs and loads its permissions.
     *
     * @param int|string|Role $role
     * @param bool $isId If true, strings are treated as IDs.
     * @return Role|null
     */
    public function resolveRole(int|string|Role $role, bool $isId = false, ?string $namespace = null): ?Role
    {
        if ($role instanceof Role) {
            if ($role->id !== null) {
                $role = $role->id;
                $isId = true;
            } else {
                $namespace = $role->namespace;
                $role = $role->name;
            }
        }

        $role = $isId ? $this->roles->findById($role, true) : $this->roles->findByName($role, $namespace);

        if (!$role) {
            return null;
        }

        $role->permissions = [];
        $role->parents = [];
        $role->children = [];

        /** @var RolePermissionRepositoryInterface $rpRepo */
        $rpRepo = resolve(RolePermissionRepositoryInterface::class);
        /** @var PermissionRepositoryInterface $pmRepo */
        $pmRepo = resolve(PermissionRepositoryInterface::class);

        $rps = $rpRepo->getRolePermissions($role);

        foreach ($rps as $rp) {
            if (isset($rp->permission_id)) {
                $p = $pmRepo->findById($rp->permission_id);
                if ($p)
                    $role->permit($p);
            }
        }

        /** @var \Vima\Core\Contracts\RoleParentRepositoryInterface $hpRepo */
        $hpRepo = resolve(RoleParentRepositoryInterface::class);
        $role->parents = $hpRepo->getParents($role);
        $role->children = $hpRepo->getChildren($role);

        return $role;
    }

    /**
     * Checks if a user has a specific role.
     *
     * @param string|int $user_id
     * @param string|Role $role
     * @return bool
     */
    public function userHasRole(string|int $user_id, string|Role $role, ?string $namespace = null): bool
    {
        $roleEntity = $this->resolveRole($role, namespace: $namespace);
        if (!$roleEntity) {
            return false;
        }

        $roles = $this->getUserRoles($user_id);
        foreach ($roles as $r) {
            if ($r->id == $roleEntity->id) {
                // If context filter is provided, check it (stub for now if not implemented in repo)
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
        $roles = $this->roles->all($namespace);

        if ($resolve) {
            $roles = array_map(fn($r) => $this->resolveRole($r), $roles);
        }

        if ($onlyGlobal) {
            $roles = array_filter($roles, fn($r) => empty($r->namespace));
        }

        return $roles;
    }

    public function findByName(string $name, ?string $namespace = null): ?Role
    {
        return $this->roles->findByName($name, $namespace);
    }

    public function deleteAll(): void
    {
        $this->roles->deleteAll();
    }

    public function getRolePermissions(string|Role $role): array
    {
        $roleEntity = $this->resolveRole($role);
        if (!$roleEntity) {
            return [];
        }

        $perms = [...$roleEntity->permissions];
        foreach ($roleEntity->parents as $parent) {
            $parentPermissions = $this->getRolePermissions($parent);
            foreach ($parentPermissions as $perm) {
                $perms[] = $perm;
            }
        }

        return $perms;
    }
}

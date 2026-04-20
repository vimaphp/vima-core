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

use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\UserRole;
use Vima\Core\Exceptions\RoleNotFoundException;
use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\Events\DefaultEventDispatcher;
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
    private RoleRepositoryInterface $roles;
    public UserRoleRepositoryInterface $userRoles;
    private RolePermissionRepositoryInterface $rolePermissions;
    private ?EventDispatcherInterface $dispatcher;
    private ?CacheInterface $cache;

    public function __construct(
        RoleRepositoryInterface $roles,
        UserRoleRepositoryInterface $userRoles,
        RolePermissionRepositoryInterface $rolePermissions,
        ?EventDispatcherInterface $dispatcher = null,
        ?CacheInterface $cache = null
    ) {
        $this->roles = $roles;
        $this->userRoles = $userRoles;
        $this->rolePermissions = $rolePermissions;
        $this->dispatcher = $dispatcher;
        $this->cache = $cache;
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
        $role = $name instanceof Role ? $name : new Role(name: $name, namespace: $namespace);

        if ($description !== null) {
            $role->description = $description;
        }

        return $this->roles->save($role);
    }

    /**
     * Find a role by identifier or instance.
     *
     * @param string|Role $role Role name or instance.
     * @return Role|null
     * @throws RoleNotFoundException
     */
    public function find(string|Role $role, ?string $namespace = null, bool $resolve = false): ?Role
    {
        $name = is_string($role) ? $role : $role->name;
        $id = !is_string($role) ? $role->id : null;
        $roleNamespace = !is_string($role) ? $role->namespace : $namespace;

        $role = $id
            ? $this->roles->findById($id, $resolve)
            : $this->roles->findByName($name, $roleNamespace);

        if (!$role) {
            throw new RoleNotFoundException($name);
        }

        if ($resolve && ($id === null || count($role->permissions) === 0)) {
            /** @var RolePermissionRepositoryInterface $rpRepo */
            $rpRepo = $this->rolePermissions;
            /** @var PermissionRepositoryInterface $pmRepo */
            $pmRepo = resolve(PermissionRepositoryInterface::class);

            $rolePermissions = $rpRepo->getRolePermissions($role);
            $role->permissions = array_map(function ($rp) use ($pmRepo) {
                return $pmRepo->findById($rp->permission_id);
            }, $rolePermissions);

            /** @var RoleParentRepositoryInterface $parentRepo */
            $parentRepo = resolve(RoleParentRepositoryInterface::class);
            $role->parents = $parentRepo->getParents($role);
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
        return $this->roles->save($role);
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
            return $role;
        }

        return $isId ? $this->roles->findById($role) : $this->roles->findByName($role, $namespace);
    }

    /**
     * Checks if a user has a specific role.
     *
     * @param string|int $user_id
     * @param string|Role $role
     * @param array $context Filters by context if provided.
     * @return bool
     */
    public function userHasRole(string|int $user_id, string|Role $role, array $context = [], ?string $namespace = null): bool
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
        return $this->roles->all($namespace, $onlyGlobal, $resolve);
    }
}

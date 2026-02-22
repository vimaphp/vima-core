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

use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\Entities\Role;
use Vima\Core\Exceptions\RoleNotFoundException;
use function Vima\Core\resolve;

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
    private PermissionManager $permissionManager;

    public function __construct()
    {
        $this->roles = resolve(RoleRepositoryInterface::class);
        $this->userRoles = resolve(UserRoleRepositoryInterface::class);
        $this->rolePermissions = resolve(RolePermissionRepositoryInterface::class);
        $this->permissionManager = resolve(PermissionManager::class);
    }

    /**
     * Create and persist a new role.
     *
     * @param string|Role $name Role name or instance.
     * @param string|null $description Optional description.
     * @return Role
     */
    public function create(string|Role $name, ?string $description = null): Role
    {
        $role = $name instanceof Role ? $name : new Role(name: $name);

        $role->description = $description;

        return $this->roles->save($role);
    }

    /**
     * Find a role by identifier or instance.
     *
     * @param string|Role $role Role name or instance.
     * @return Role|null
     * @throws RoleNotFoundException
     */
    public function find(string|Role $role): ?Role
    {
        $name = is_string($role) ? $role : $role->name;
        $id = !is_string($role) ? $role->id : null;

        $role = $id
            ? $this->roles->findById($id)
            : $this->roles->findByName($name);

        if (!$role) {
            throw new RoleNotFoundException($name);
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
     * Delete a role.
     *
     * @param Role $name
     * @return void
     */
    public function delete(Role $name): void
    {
        $this->roles->delete($name);
    }

    /**
     * Retrieve all roles assigned to a user.
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
    public function resolveRole(int|string|Role $role, bool $isId = false): ?Role
    {
        $id = null;
        $name = null;

        if ($role instanceof Role) {
            $id = $role->id;
            $name = $role->name;
        } elseif (is_int($role)) {
            $id = $role;
        } elseif (is_string($role)) {
            if ($isId) {
                // If explicitly marked as ID, try to parse string as int
                $id = ctype_digit($role) ? (int) $role : $role;
            } else {
                $name = $role;
            }
        }

        if ($id !== null) {
            $role = $this->roles->findById($id);
        } elseif ($name !== null) {
            $role = $this->roles->findByName($name);
        } else {
            return null; // no valid identifier
        }

        if (!($role instanceof Role)) {
            return null;
        }

        $role->permissions = $this->rolePermissions->getRolePermissions($role);

        return $role;
    }
}

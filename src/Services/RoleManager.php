<?php

namespace Vima\Core\Services;

use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Entities\Role;
use Vima\Core\Exceptions\RoleNotFoundException;

class RoleManager
{
    private RoleRepositoryInterface $roles;
    public UserRoleRepositoryInterface $userRoles;
    private RolePermissionRepositoryInterface $rolePermissions;
    private PermissionManager $permissionManager;
    public function __construct()
    {
        $DC = DependencyContainer::$instance;

        $this->roles = &$DC->roles;
        $this->userRoles = &$DC->userRoles;
        $this->rolePermissions = &$DC->rolePermissions;
        $this->permissionManager = new PermissionManager();
    }
    public function create(string|Role $name, ?string $description = null): Role
    {
        $role = $name instanceof Role ? $name : new Role(name: $name);

        $role->description = $description;

        return $this->roles->save($role);
    }
    public function find(string|Role $role): ?Role
    {
        $name = is_string($role) ? $role : $role->name;
        $id = !is_string($role) ? $role->id : null;

        $role = $id
            ? $this->roles->findById($id)
            : $this->roles->findByName($name);

        if (!$role) {
            throw new RoleNotFoundException("Role with the name [$name] not found");
        }

        return $role;
    }

    public function save(Role $role): Role
    {
        return $this->roles->save($role);
    }

    public function grantRole(string|Role $role): void
    {
    }

    public function delete(Role $name): void
    {
        $this->roles->delete($name);
    }


    /**
     * Gets user roles
     * @param string|int $user_id
     * @return Role[]
     */
    public function getUserRoles(string|int $user_id, bool $resolve = false): array
    {
        return $this->userRoles->getRolesForUser($user_id, $resolve);
    }

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

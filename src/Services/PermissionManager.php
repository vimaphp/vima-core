<?php

namespace Vima\Core\Services;

use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Entities\Permission;
use Vima\Core\Exceptions\PermissionNotFoundException;

class PermissionManager
{
    private PermissionRepositoryInterface $permissions;
    private UserPermissionRepositoryInterface $userPermissions;
    public function __construct()
    {
        $DC = DependencyContainer::$instance;

        $this->permissions = &$DC->permissions;
        $this->userPermissions = &$DC->userPermissions;
    }

    public function create(string|Permission $name, ?string $description = null): Permission
    {
        $permission = $name instanceof Permission ? $name : new Permission($name);

        $permission->description = $description;

        return $this->permissions->save(permission: $permission);
    }

    public function find(string|Permission $permission): ?Permission
    {
        $name = is_string($permission) ? $permission : $permission->name;
        $id = !is_string($permission) ? $permission->id : null;

        $permission = $id
            ? $this->permissions->findById($name)
            : $this->permissions->findByName($name);

        if (!$permission) {
            throw new PermissionNotFoundException("Permission with the name [$name] not found");
        }

        return $permission;
    }

    /**
     * Returns the user spcific permissions assigned to the user
     * @param int|string $user_id
     * @return array<Permission|null>
     */
    public function getUserSpecificPermissions(int|string $user_id): array
    {
        $userPermissions = $this->userPermissions->findByUserId($user_id);

        $permissons = [];

        foreach ($userPermissions as $up) {
            $permissons[] = $this->permissions->findById($up->permission_id);
        }

        return array_filter($permissons);
    }

    public function delete(Permission $name): void
    {
        $this->permissions->delete($name);
    }

    public function save(Permission $permission): Permission
    {
        return $this->permissions->save($permission);
    }
}

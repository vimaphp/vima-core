<?php

namespace Vima\Core\Services;

use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Entities\Permission;
use Vima\Core\Exceptions\PermissionNotFoundException;

class PermissionManager
{
    public function __construct(private PermissionRepositoryInterface $permissions)
    {
    }

    public function create(string|Permission $name): Permission
    {
        $permission = $name instanceof Permission ? $name : new Permission($name);
        $this->permissions->save(permission: $permission);
        return $permission;
    }

    public function find(string $name): Permission
    {
        $permission = $this->permissions->findByName($name);
        if (!$permission) {
            throw new PermissionNotFoundException($name);
        }
        return $permission;
    }

    public function delete(Permission $name): void
    {
        $this->permissions->delete($name);
    }
}

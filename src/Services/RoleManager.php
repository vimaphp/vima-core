<?php

namespace Vima\Core\Services;

use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Entities\Role;
use Vima\Core\Exceptions\RoleNotFoundException;

class RoleManager
{
    public function __construct(private RoleRepositoryInterface $roles)
    {
    }

    public function create(string|Role $name): Role
    {
        $role = $name instanceof Role ? $name : new Role($name);
        $this->roles->save($role);
        return $role;
    }

    public function find(string $name): Role
    {
        $role = $this->roles->findByName($name);
        if (!$role) {
            throw new RoleNotFoundException($name);
        }
        return $role;
    }

    public function save(Role $role): void
    {
        $this->roles->save($role);
    }

    public function delete(Role $name): void
    {
        $this->roles->delete($name);
    }
}

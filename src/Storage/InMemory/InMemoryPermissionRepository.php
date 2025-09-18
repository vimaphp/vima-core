<?php

namespace Vima\Core\Storage\InMemory;

use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Entities\Permission;

class InMemoryPermissionRepository implements PermissionRepositoryInterface
{
    /** @var Permission[] */
    private array $permissions = [];

    public function findByName(string $name): ?Permission
    {
        return $this->permissions[$name] ?? null;
    }

    public function save(Permission $permission): void
    {
        $this->permissions[$permission->getName()] = $permission;
    }

    public function delete(Permission $permission): void
    {
        unset($this->permissions[$permission->getName()]);
    }

    public function all(): array
    {
        return $this->permissions;
    }
}

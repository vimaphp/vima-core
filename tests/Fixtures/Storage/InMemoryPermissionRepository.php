<?php

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Entities\Permission;

class InMemoryPermissionRepository implements PermissionRepositoryInterface
{
    /** @var Permission[] */
    private array $permissions = [];

    private int $id = 0;

    public function findByName(string $name): ?Permission
    {
        return $this->permissions[$name] ?? null;
    }
    public function findById(int|string $id): ?Permission
    {
        $permission = null;
        $perm = array_filter($this->permissions, function ($p) use ($id, &$permission) {
            $check = $p->id === $id;
            if ($check) {
                $permission = $p;
            }

            return $check;
        });
        return $permission;
    }

    public function save(Permission $permission): Permission
    {
        if (!$permission->id) {
            $permission->id = $this->id;

            $this->id++;
        }

        $this->permissions[$permission->name] = $permission;

        return $permission;
    }

    public function delete(Permission $permission): void
    {
        unset($this->permissions[$permission->name]);
    }

    public function all(): array
    {
        return $this->permissions;
    }
}

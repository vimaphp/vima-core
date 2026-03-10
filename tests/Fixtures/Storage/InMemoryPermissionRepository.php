<?php

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Entities\Permission;

class InMemoryPermissionRepository implements PermissionRepositoryInterface
{
    /** @var Permission[] */
    private array $permissions = [];

    private int $id = 0;

    public function findByName(string $name, ?string $namespace = null): ?Permission
    {
        $key = $namespace . ':' . $name;
        return $this->permissions[$key] ?? null;
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
        if ($permission->id === null) {
            $permission->id = $this->id;

            $this->id++;
        }

        $key = $permission->namespace . ':' . $permission->name;
        $this->permissions[$key] = $permission;

        return $permission;
    }

    public function delete(Permission $permission): void
    {
        $key = $permission->namespace . ':' . $permission->name;
        unset($this->permissions[$key]);
    }

    public function all(?string $namespace = null): array
    {
        if ($namespace === null) {
            return array_values($this->permissions);
        }

        return array_values(array_filter($this->permissions, fn($p) => $p->namespace === $namespace));
    }

    public function deleteAll(): void
    {
        $this->permissions = [];
        $this->id = 0;
    }
}

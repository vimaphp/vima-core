<?php

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Entities\Bare\BarePermission;

class InMemoryPermissionRepository implements PermissionRepositoryInterface
{
    /** @var BarePermission[] */
    private array $permissions = [];

    private int $id = 1;

    public function findByName(string $name, ?string $namespace = null): ?BarePermission
    {
        $key = ($namespace ?? 'global') . ':' . $name;
        return $this->permissions[$key] ?? null;
    }

    public function findById(int|string $id): ?BarePermission
    {
        foreach ($this->permissions as $p) {
            if ($p->id == $id) {
                return $p;
            }
        }
        return null;
    }

    public function save(BarePermission $permission): BarePermission
    {
        if ($permission->id === null) {
            $permission->id = $this->id++;
        }

        $key = ($permission->namespace ?? 'global') . ':' . $permission->name;
        $this->permissions[$key] = $permission;

        return $permission;
    }

    public function delete(BarePermission $permission): void
    {
        $key = ($permission->namespace ?? 'global') . ':' . $permission->name;
        unset($this->permissions[$key]);
    }

    public function all(?string $namespace = null, bool $onlyGlobal = false): array
    {
        $filtered = array_values($this->permissions);

        if ($namespace !== null) {
            $filtered = array_filter($this->permissions, fn($p) => $p->namespace === $namespace);
        } elseif ($onlyGlobal) {
            $filtered = array_filter($this->permissions, fn($p) => empty($p->namespace));
        }

        return array_values($filtered);
    }

    public function deleteAll(): void
    {
        $this->permissions = [];
        $this->id = 1;
    }
}

<?php

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\RolePermission;
use function Vima\Core\resolve;

class InMemoryRoleRepository implements RoleRepositoryInterface
{
    /** @var Role[] */
    private array $roles = [];

    private int $id = 0;

    public function findById(int|string $id, bool $resolve = false): ?Role
    {
        $role = null;
        array_filter($this->roles, function ($p) use ($id, &$role) {
            $check = $p->id === $id;
            if ($check) {
                $role = $p;
            }

            return $check;
        });

        /** @var RolePermissionRepositoryInterface */
        $rpRepo = resolve(RolePermissionRepositoryInterface::class);

        /** @var PermissionRepositoryInterface */
        $pmRepo = resolve(PermissionRepositoryInterface::class);

        if ($role && $resolve) {
            $rps = array_filter($rpRepo->getRolePermissions($role));

            foreach ($rps as $rp) {
                if (isset($rp->permission_id)) {
                    $role->permissions[] = $pmRepo->findById($rp->permission_id);

                }
            }
        }

        return $role;
    }

    public function findByName(string $name, ?string $namespace = null): ?Role
    {
        $key = $namespace . ':' . $name;
        return $this->roles[$key] ?? null;
    }

    public function save(Role $role): Role
    {
        if ($role->id === null) {
            $role->id = $this->id;

            $this->id++;
        }

        $key = $role->namespace . ':' . $role->name;
        $this->roles[$key] = $role;

        // get role permission memory storage
        $rpMemory = resolve(RolePermissionRepositoryInterface::class);
        $pmMemory = resolve(PermissionRepositoryInterface::class);

        // add permssions to the role permission memory storage
        $i = 0;
        foreach ($role->permissions as $pm) {
            $permission = $pmMemory->findByName($pm->name, $pm->namespace);

            if (!$permission) {
                $permission = $pmMemory->save($pm);
            }

            $pm = $permission;

            $rpMemory->assign(RolePermission::define(
                role_id: $role->id,
                permission_id: $pm->id
            ));

            $role->permissions[$i] = $pm;
            $i++;
        }

        return $role;
    }

    public function delete(Role $role): void
    {
        $key = $role->namespace . ':' . $role->name;
        unset($this->roles[$key]);
    }

    public function all(?string $namespace = null): array
    {
        if ($namespace === null) {
            return array_values($this->roles);
        }

        return array_values(array_filter($this->roles, fn($role) => $role->namespace === $namespace));
    }

    public function deleteAll(): void
    {
        $this->roles = [];
        $this->id = 0;
    }

    public function getParents(Role $role): array
    {
        return $role->parents;
    }

    public function getChildren(Role $role): array
    {
        $children = [];
        foreach ($this->roles as $r) {
            foreach ($r->parents as $parent) {
                if ($parent->name === $role->name) {
                    $children[] = $r;
                    break;
                }
            }
        }
        return $children;
    }
}

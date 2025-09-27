<?php

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\RolePermission;

class InMemoryRoleRepository implements RoleRepositoryInterface
{
    /** @var Role[] */
    private array $roles = [];

    private int $id = 0;

    public function findById(int|string $id): ?Role
    {
        $role = null;
        array_filter($this->roles, function ($p) use ($id, &$role) {
            $check = $p->id === $id;
            if ($check) {
                $role = $p;
            }

            return $check;
        });

        return $role;
    }

    public function findByName(string $name): ?Role
    {
        return $this->roles[$name] ?? null;
    }

    public function save(Role $role): Role
    {
        if (!$role->id) {
            $role->id = $this->id;

            $this->id++;
        }

        $this->roles[$role->name] = $role;

        // get role permission memory storage
        $rpMemory = (DependencyContainer::$instance)->rolePermissions;
        $pmMemory = (DependencyContainer::$instance)->permissions;

        // add permssions to the role permission memory storage
        $i = 0;
        foreach ($role->permissions as $pm) {
            $permission = $pmMemory->findByName($pm->name);

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
        unset($this->roles[$role->name]);
    }

    public function all(): array
    {
        return $this->roles;
    }
}

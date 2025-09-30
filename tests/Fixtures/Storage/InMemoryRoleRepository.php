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
        $rpMemory = resolve(RolePermissionRepositoryInterface::class);
        $pmMemory = resolve(PermissionRepositoryInterface::class);

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

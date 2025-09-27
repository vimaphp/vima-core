<?php
declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Entities\{Role, Permission, RolePermission};
use Vima\Core\DependencyContainer;

class InMemoryRolePermissionRepository implements RolePermissionRepositoryInterface
{
    /** @var RolePermission[] */
    private array $rolePermissions = [];

    public function getRolePermissions(Role $role): array
    {
        /** @var RolePermission[] */
        $rolePerms = array_values(
            array_filter(
                $this->rolePermissions,
                fn(RolePermission $rp) => (string) $rp->role_id === (string) $role->id
            )
        );

        // get the permMemory
        $permsMemory = (DependencyContainer::$instance)->permissions;

        $permissions = [];

        foreach ($rolePerms as $rp) {
            $permissions[] = $permsMemory->findById($rp->permission_id);
        }

        return $permissions;
    }

    public function getPermissionRoles(Permission $permission): array
    {
        return array_values(
            array_filter(
                $this->rolePermissions,
                fn(RolePermission $rp) => (string) $rp->permission_id === (string) $permission->id
            )
        );
    }

    public function all(): array
    {
        return array_values($this->rolePermissions);
    }

    public function assign(RolePermission $permission): void
    {
        // prevent duplicates (same role_id + permission_id)
        foreach ($this->rolePermissions as $rp) {
            if (
                (string) $rp->role_id === (string) $permission->role_id &&
                (string) $rp->permission_id === (string) $permission->permission_id
            ) {
                return;
            }
        }

        // auto-generate ID if not set
        if ($permission->id === null) {
            $permission->id = count($this->rolePermissions) + 1;
        }

        $this->rolePermissions[] = $permission;
    }

    public function revoke(RolePermission $permission): void
    {
        $this->rolePermissions = array_values(
            array_filter(
                $this->rolePermissions,
                fn(RolePermission $rp) =>
                !(
                    (string) $rp->role_id === (string) $permission->role_id &&
                    (string) $rp->permission_id === (string) $permission->permission_id
                )
            )
        );
    }
}

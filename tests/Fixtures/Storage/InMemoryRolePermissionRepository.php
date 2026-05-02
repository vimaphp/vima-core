<?php
declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Entities\Bare\BareRole;
use Vima\Core\Entities\Bare\BarePermission;
use Vima\Core\Entities\Bare\BareRolePermission;

class InMemoryRolePermissionRepository implements RolePermissionRepositoryInterface
{
    /** @var BareRolePermission[] */
    private array $rolePermissions = [];

    public function getRolePermissions(BareRole $role): array
    {
        return array_values(
            array_filter(
                $this->rolePermissions,
                fn(BareRolePermission $rp) => (string) $rp->role_id === (string) $role->id
            )
        );
    }

    public function getPermissionRoles(BarePermission $permission): array
    {
        return array_values(
            array_filter(
                $this->rolePermissions,
                fn(BareRolePermission $rp) => (string) $rp->permission_id === (string) $permission->id
            )
        );
    }

    public function all(): array
    {
        return array_values($this->rolePermissions);
    }

    public function assign(BareRolePermission $permission): void
    {
        // prevent duplicates (same role_id + permission_id)
        foreach ($this->rolePermissions as $rp) {
            if (
                (string) $rp->role_id === (string) $permission->role_id &&
                (string) $rp->permission_id === (string) $permission->permission_id
            ) {
                $rp->constraints = $permission->constraints;
                return;
            }
        }

        // auto-generate ID if not set
        if ($permission->id === null) {
            $permission->id = count($this->rolePermissions) + 1;
        }

        $this->rolePermissions[] = $permission;
    }

    public function revoke(BareRolePermission $permission): void
    {
        $this->rolePermissions = array_values(
            array_filter(
                $this->rolePermissions,
                fn(BareRolePermission $rp) =>
                !(
                    (string) $rp->role_id === (string) $permission->role_id &&
                    (string) $rp->permission_id === (string) $permission->permission_id
                )
            )
        );
    }

    public function deleteAll(): void
    {
        $this->rolePermissions = [];
    }
}

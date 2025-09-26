<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Entities\RolePermission;

interface RolePermissionRepositoryInterface
{
    /**
     * Returns the permissions for the given role
     * @param int $roleId
     * @return ?RolePermission
     */
    public function findByRoleAndPermission(int $roleId, int $permissionId): ?RolePermission;

    /**
     * Returns all the available role_permissions
     *  @return RolePermission[] 
     */
    public function all(): array;

    public function assign(RolePermission $permission): void;

    public function revoke(RolePermission $permission): void;
}
<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Entities\RolePermission;

interface RolePermissionRepositoryInterface
{
    /**
     * Returns Permssions for the role specified
     * @param Role $args
     * @return RolePermission[]
     */
    public function getRolePermissions(Role $role): array;

    /**
     * Returns the roles assigned to the specified permission
     * @param Permission $permission
     * @return RolePermission[]
     */
    public function getPermissionRoles(Permission $permission): array;

    /**
     * Returns all the available role_permissions
     *  @return RolePermission[] 
     */
    public function all(): array;

    public function assign(RolePermission $permission): void;

    public function revoke(RolePermission $permission): void;
}
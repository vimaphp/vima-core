<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Entities\Bare\BareRole;
use Vima\Core\Entities\Bare\BarePermission;
use Vima\Core\Entities\Bare\BareRolePermission;

interface RolePermissionRepositoryInterface
{
    /**
     * Returns Permssions for the role specified
     * @param BareRole $role
     * @return BareRolePermission[]
     */
    public function getRolePermissions(BareRole $role): array;

    /**
     * Returns the roles assigned to the specified permission
     * @param BarePermission $permission
     * @return BareRolePermission[]
     */
    public function getPermissionRoles(BarePermission $permission): array;

    /**
     * Returns all the available role_permissions
     *  @return BareRolePermission[] 
     */
    public function all(): array;

    public function assign(BareRolePermission $permission): void;

    public function revoke(BareRolePermission $permission): void;

    /**
     * Clear all role permissions.
     *
     * @return void
     */
    public function deleteAll(): void;
}
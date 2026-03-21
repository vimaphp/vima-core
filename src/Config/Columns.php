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

namespace Vima\Core\Config;

final class Columns
{
    public RoleColumns $roles;
    public PermissionColumns $permissions;
    public RolePermissionColumns $rolePermissions;
    public UserRoleColumns $userRoles;
    public UserPermissionColumns $userPermissions;
    public RoleParentColumns $roleParents;
    public UserDenyColumns $userDenies;

    public function __construct(
        ?RoleColumns $roles = null,
        ?PermissionColumns $permissions = null,
        ?RolePermissionColumns $rolePermissions = null,
        ?UserRoleColumns $userRoles = null,
        ?UserPermissionColumns $userPermissions = null,
        ?RoleParentColumns $roleParents = null,
        ?UserDenyColumns $userDenies = null
    ) {
        $this->roles = $roles ?? new RoleColumns();
        $this->permissions = $permissions ?? new PermissionColumns();
        $this->rolePermissions = $rolePermissions ?? new RolePermissionColumns();
        $this->userRoles = $userRoles ?? new UserRoleColumns();
        $this->userPermissions = $userPermissions ?? new UserPermissionColumns();
        $this->roleParents = $roleParents ?? new RoleParentColumns();
        $this->userDenies = $userDenies ?? new UserDenyColumns();
    }
}

final class RoleColumns
{
    public function __construct(
        public string $id = 'id',
        public string $name = 'name',
        public ?string $description = 'description',
        public ?string $namespace = 'namespace',
        public ?string $context = 'context',
    ) {
    }
}

final class PermissionColumns
{
    public function __construct(
        public string $id = 'id',
        public string $name = 'name',
        public ?string $description = 'description',
        public ?string $namespace = 'namespace',
    ) {
    }
}

final class RolePermissionColumns
{
    public function __construct(
        public string $id = 'id',
        public string $roleId = 'role_id',
        public string $permissionId = 'permission_id',
    ) {
    }
}

final class UserRoleColumns
{
    public function __construct(
        public string $id = 'id',
        public string $userId = 'user_id',
        public string $roleId = 'role_id',
    ) {
    }
}
final class UserPermissionColumns
{
    public function __construct(
        public string $id = 'id',
        public string $userId = 'user_id',
        public string $permissionId = 'permission_id',
    ) {
    }
}

final class RoleParentColumns
{
    public function __construct(
        public string $id = 'id',
        public string $roleId = 'role_id',
        public string $parentId = 'parent_id',
    ) {
    }
}

final class UserDenyColumns
{
    public function __construct(
        public string $id = 'id',
        public string $userId = 'user_id',
        public string $permissionId = 'permission_id',
        public string $namespace = 'namespace',
        public string $reason = 'reason',
        public string $createdAt = 'created_at',
    ) {
    }
}

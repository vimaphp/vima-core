<?php

declare(strict_types=1);

namespace Vima\Core\Config;

final class Columns
{
    public function __construct(
        public RoleColumns $roles,
        public PermissionColumns $permissions,
        public RolePermissionColumns $rolePermissions,
        public UserRoleColumns $userRoles,
        public UserPermissionColumns $userPermissions
    ) {
    }
}

final class RoleColumns
{
    public function __construct(
        public string $id = 'id',
        public string $name = 'name',
        public ?string $description = 'description',
    ) {
    }
}

final class PermissionColumns
{
    public function __construct(
        public string $id = 'id',
        public string $name = 'name',
        public ?string $description = 'description',
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
        public string $roleId = 'permission_id',
    ) {
    }
}

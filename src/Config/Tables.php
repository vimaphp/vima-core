<?php

declare(strict_types=1);

namespace Vima\Core\Config;

final class Tables
{
    public function __construct(
        public string $roles = 'roles',
        public string $permissions = 'permissions',
        public string $rolePermissions = 'role_permissions',
        public string $userRoles = 'user_roles',
        public string $userPermissions = 'user_permissions',
    ) {
    }
}
<?php

declare(strict_types=1);

namespace Vima\Core\Config;

final class Tables
{
    public function __construct(
        public string $roles = 'roles',
        public string $permissions = 'permissions',
        public string $rolePermission = 'role_permission',
        public string $userRoles = 'user_roles',
    ) {
    }
}
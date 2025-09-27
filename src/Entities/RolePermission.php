<?php

declare(strict_types=1);

namespace Vima\Core\Entities;

class RolePermission
{
    public function __construct(
        public int|string $role_id,
        public int|string $permission_id,
        public int|string|null $id = null,
    ) {
    }

    public static function define(int|string $role_id, int|string $permission_id): RolePermission
    {
        return new self(role_id: $role_id, permission_id: $permission_id);
    }
}
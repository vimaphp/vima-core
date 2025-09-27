<?php

declare(strict_types=1);

namespace Vima\Core\Entities;

class UserPermission
{
    public function __construct(
        public int|string|null $id = null,
        public int|string $user_id,
        public int|string $permission_id
    ) {
    }

    public static function define(int|string $user_id, int|string $permission_id): UserPermission
    {
        return new self(user_id: $user_id, permission_id: $permission_id);
    }
}
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

namespace Vima\Core\Entities;

class UserRole
{
    public function __construct(
        public string|int $user_id,
        public int|string $role_id,
        public string|int|null $id = null,
    ) {
    }

    public static function define(int|string $user_id, int|string $role_id): UserRole
    {
        return new self(role_id: $role_id, user_id: $user_id);
    }
}
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

/**
 * Class UserDeny
 * 
 * Represents an explicit denial of a permission for a user.
 *
 * @package Vima\Core\Entities
 */
class UserDeny
{
    public function __construct(
        public int|string $user_id,
        public int|string $permission_id,
        public ?int $id = null
    ) {
    }

    public static function define(int|string $user_id, int|string $permission_id): self
    {
        return new self($user_id, $permission_id);
    }
}

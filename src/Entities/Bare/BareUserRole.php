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

namespace Vima\Core\Entities\Bare;

/**
 * Class BareUserRole
 * 
 * Bare data entity representing the assignment of a role to a user.
 */
class BareUserRole
{
    public function __construct(
        public int|string|null $id = null,
        public int|string|null $user_id = null,
        public int|string|null $role_id = null,
        public ?array $context = []
    ) {
    }
}

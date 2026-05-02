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
 * Class BareUserPermission
 * 
 * Bare data entity representing a direct permission assignment to a user.
 */
class BareUserPermission
{
    public function __construct(
        public int|string|null $id = null,
        public int|string|null $user_id = null,
        public int|string|null $permission_id = null,
        public ?array $constraints = []
    ) {
    }
}

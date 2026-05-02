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
 * Class BareUserDeny
 * 
 * Bare data entity representing an explicit permission denial for a user.
 */
class BareUserDeny
{
    public function __construct(
        public int|string|null $id = null,
        public int|string|null $user_id = null,
        public int|string|null $permission_id = null,
        public ?string $namespace = null,
        public ?string $reason = null,
        public ?string $expires_at = null,
        public ?string $created_at = null
    ) {
    }
}

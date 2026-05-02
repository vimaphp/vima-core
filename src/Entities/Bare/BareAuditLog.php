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
 * Class BareAuditLog
 * 
 * Bare data entity representing an audit log entry.
 */
class BareAuditLog
{
    public function __construct(
        public int|string|null $id = null,
        public int|string|null $user_id = null,
        public ?string $permission = null,
        public ?string $namespace = null,
        public ?int $result = null,
        public ?string $reason = null,
        public ?string $arguments = null,
        public ?string $created_at = null
    ) {
    }
}

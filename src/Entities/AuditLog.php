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
 * Class AuditLog
 * 
 * Represents an audit log entry with domain context.
 */
class AuditLog
{
    public function __construct(
        public int|string|null $user_id = null,
        public ?string $permission = null,
        public ?string $namespace = null,
        public ?int $result = null,
        public ?string $reason = null,
        public ?string $arguments = null,
        public int|string|null $id = null,
        public ?string $created_at = null
    ) {
    }

    public static function define(
        int|string $userId,
        string $permission,
        ?string $namespace = null,
        ?int $result = null,
        ?string $reason = null,
        ?string $arguments = null
    ): self {
        return new self(
            user_id: $userId,
            permission: $permission,
            namespace: $namespace,
            result: $result,
            reason: $reason,
            arguments: $arguments
        );
    }
}

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
 * Class BareRole
 * 
 * Aggregated role entity containing its bare data and all associated relationships.
 */
class BareRole
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
        public ?string $namespace = null,
        public ?string $description = null,
        public ?array $context = [],
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {
    }
}

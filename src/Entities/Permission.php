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

use Vima\Core\Contracts\AccessManagerInterface;
use function Vima\Core\resolve;

/**
 * Class Permission
 * 
 * Represents an individual permission or ability.
 *
 * @package Vima\Core\Entities
 */
class Permission
{
    /**
     * Permission constructor.
     *
     * @param string $name Unique name of the permission.
     * @param string|null $description Optional description.
     * @param int|string|null $id Unique identifier from storage.
     */
    public function __construct(
        public string $name,
        public ?string $namespace = null,
        public ?string $description = null,
        public int|string|null $id = null,
    ) {
    }

    /**
     * Static helper to define a new permission.
     *
     * @param string $name
     * @param string|null $description
     * @return self
     */
    public static function define(string $name, ?string $description = null, ?string $namespace = null): self
    {
        return new self(name: $name, namespace: $namespace, description: $description);
    }

    /**
     * Persist this permission via the AccessManager.
     *
     * @return Permission
     */
    public function save(): self
    {
        /** @var \Vima\Core\Contracts\AccessManagerInterface */
        $manager = resolve(AccessManagerInterface::class);
        return $manager->updatePermission($this);
    }
}

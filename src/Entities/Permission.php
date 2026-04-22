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
     * @param string|null $namespace Optional namespace.
     * @param string|null $description Optional description.
     * @param int|string|null $id Unique identifier from storage.
     */
    public function __construct(
        public string $name,
        public ?string $namespace = null,
        public ?string $description = null,
        public int|string|null $id = null,
        public bool $denied = false,
    ) {
        if (str_contains($this->name, ':')) {
            [$ns, $n] = explode(':', $this->name, 2);
            $this->namespace = $ns;
            $this->name = $n;
        }
    }

    /**
     * Static helper to define a new permission.
     *
     * @param string $name
     * @param string|null $description
     * @return self
     */
    public static function define(string $name, ?string $description = null, ?string $namespace = null, bool $denied = false): self
    {
        $perm = new self(name: $name, namespace: $namespace, description: $description);
        $perm->denied = $denied;
        return $perm;
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

    /**
     * Validates a permission name against its namespaced name and the provided name. Uses the name if no namespace is set
     * @param string $name
     * @return bool
     */
    public function validateNamespacedName(string $name): bool
    {
        return $name === $this->getFullName();
    }

    public function getFullName(): string
    {
        return $this->namespace ? "{$this->namespace}:{$this->name}" : $this->name;
    }
}

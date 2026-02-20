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
 * Class Role
 * 
 * Represents a user role consisting of a set of permissions.
 *
 * @package Vima\Core\Entities
 */
class Role
{
    /**
     * Role constructor.
     *
     * @param string $name Unique name of the role.
     * @param Permission[] $permissions Array of Permission entities.
     * @param string|null $description Optional description.
     * @param int|string|null $id Unique identifier from storage.
     */
    public function __construct(
        public string $name,
        public array $permissions = [],
        public ?string $description = null,
        public int|string|null $id = null,
    ) {
    }

    /**
     * Static helper to define a new role.
     *
     * @param string $name
     * @param array $permissions Array of permission names or entities.
     * @param string|null $description
     * @return self
     */
    public static function define(string $name, array $permissions = [], ?string $description = null): self
    {
        $role = new self(name: $name);

        foreach ($permissions as $perm) {
            $permission = $perm instanceof Permission ? $perm : new Permission(name: $perm);
            $role->addPermission($permission);
        }

        $role->description = $description;

        return $role;
    }

    /**
     * Assign a permission to this role.
     *
     * @param Permission $permission
     * @return $this
     */
    public function addPermission(Permission $permission): self
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }

        return $this;
    }

    /**
     * Remove a permission from this role.
     *
     * @param Permission $permission
     * @return $this
     */
    public function removePermission(Permission $permission): self
    {
        $this->permissions = array_filter(
            $this->permissions,
            fn($p) => $p !== $permission
        );

        return $this;
    }

    /**
     * Check if this role contains any of the given permissions.
     *
     * @param string ...$permissions Variable list of permission names.
     * @return bool
     */
    public function hasPermission(string ...$permissions): bool
    {
        $perms = [];

        foreach ($this->permissions as $p) {
            $perms[] = $p->name;
        }

        foreach ($permissions as $p) {
            if (in_array($p, $perms)) {
                return true;
            }
        }

        return false;
    }
}

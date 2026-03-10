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
        public ?string $namespace = null,
        public array $permissions = [],
        public ?string $description = null,
        public int|string|null $id = null,
        public array $context = [],
        /** @var Role[] */
        public array $parents = [],
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
    public static function define(string $name, array $permissions = [], ?string $description = null, ?string $namespace = null, array $context = []): self
    {
        $role = new self(name: $name, namespace: $namespace, context: $context);

        foreach ($permissions as $perm) {
            $permission = $perm instanceof Permission ? $perm : new Permission(name: $perm);
            $role->permit($permission);
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
    public function permit(Permission|string $permission): self
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission instanceof Permission ? $permission : new Permission(name: $permission);
        }

        return $this;
    }

    /**
     * Remove a permission from this role.
     *
     * @param Permission $permission
     * @return $this
     */
    public function forbid(Permission $permission): self
    {
        $this->permissions = array_filter(
            $this->permissions,
            fn($p) => $p !== $permission
        );

        return $this;
    }

    /**
     * Persist this role via the AccessManager.
     *
     * @return Role
     */
    public function save(): self
    {
        /** @var \Vima\Core\Contracts\AccessManagerInterface */
        $manager = resolve(AccessManagerInterface::class);
        return $manager->updateRole($this);
    }

    /**
     * Delete this role via the AccessManager.
     *
     * @return void
     */
    public function delete(): void
    {
        /** @var \Vima\Core\Contracts\AccessManagerInterface */
        $manager = resolve(AccessManagerInterface::class);
        $manager->deleteRole($this);
    }

    /**
     * Set the parent roles.
     *
     * @param Role[] $parents
     * @return $this
     */
    public function setParents(array $parents): self
    {
        $this->parents = $parents;
        return $this;
    }

    /**
     * Add a parent role.
     *
     * @param Role $role
     * @return $this
     */
    public function inherit(Role $role): self
    {
        if (!in_array($role, $this->parents, true)) {
            $this->parents[] = $role;
        }

        return $this;
    }

    /**
     * Flattens all permissions from this role and its parents recursively.
     *
     * @param string[] &$visited Map of role names to track circular dependencies.
     * @return Permission[]
     */
    public function getAllPermissions(array &$visited = []): array
    {
        if (isset($visited[$this->name])) {
            return [];
        }

        $visited[$this->name] = true;

        $permissions = $this->permissions;

        foreach ($this->parents as $parent) {
            $permissions = array_merge($permissions, $parent->getAllPermissions($visited));
        }

        return $permissions;
    }

    /**
     * Check if this role contains any of the given permissions.
     *
     * @param string ...$permissions Variable list of permission names.
     * @return bool
     */
    public function isPermitted(string ...$permissions): bool
    {
        $perms = array_map(fn($p) => $p->name, $this->getAllPermissions());

        foreach ($permissions as $p) {
            if (in_array($p, $perms)) {
                return true;
            }
        }

        return false;
    }
}

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
 * Represents a set of permissions or a user role.
 *
 * @package Vima\Core\Entities
 */
class Role
{
    /**
     * Role constructor.
     *
     * @param string $name
     * @param Permission[] $permissions
     * @param Role[] $parents
     * @param Role[] $children
     * @param string|null $namespace
     * @param string|null $description
     * @param array $context Additional context metadata.
     * @param int|string|null $id Unique identifier from storage.
     */
    public function __construct(
        public string $name,
        public array $permissions = [],
        public array $parents = [],
        public array $children = [],
        public ?string $namespace = null,
        public ?string $description = null,
        public array $context = [],
        public int|string|null $id = null,
    ) {
    }

    /**
     * Static helper to define a new role with permissions.
     *
     * @param string $name
     * @param array $permissions Array of permission names or entities.
     * @param string|null $description
     * @return self
     */
    public static function define(string $name, array $permissions = [], ?string $description = null, ?string $namespace = null, array $context = [], array $parents = [], array $children = []): self
    {
        $role = new self(name: $name, namespace: $namespace, context: $context);

        foreach ($permissions as $perm) {
            $permission = $perm instanceof Permission ? $perm : new Permission(name: $perm);
            $role->permit($permission);
        }

        foreach ($parents as $parent) {
            $role->inherit($parent);
        }

        foreach ($children as $child) {
            $role->addChild($child);
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
    public function permit(Permission|string $permission, ?string $namespace = null): self
    {
        $target = $permission instanceof Permission ? $permission : new Permission(name: $permission, namespace: $namespace);

        $exists = false;
        foreach ($this->permissions as $p) {
            if ($p->name === $target->name && $p->namespace === $target->namespace) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $this->permissions[] = $target;
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
            fn($p) => $p->name !== $permission->name || $p->namespace !== $permission->namespace
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
     * Set the children roles.
     *
     * @param Role[] $children
     * @return $this
     */
    public function setChildren(array $children): self
    {
        $this->children = $children;
        return $this;
    }

    /**
     * Add a parent role to inherit from.
     *
     * @param Role $parent
     * @return $this
     */
    public function inherit(Role $parent): self
    {
        $exists = false;
        foreach ($this->parents as $p) {
            if ($p->name === $parent->name && $p->namespace === $parent->namespace) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $this->parents[] = $parent;
        }

        return $this;
    }

    /**
     * Add a child role to inherit from.
     *
     * @param Role $child
     * @return $this
     */
    public function addChild(Role $child): self
    {
        $exists = false;
        foreach ($this->children as $c) {
            if ($c->name === $child->name && $c->namespace === $child->namespace) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $this->children[] = $child;
        }

        return $this;
    }

    /**
     * Flattens all permissions from this role and its parents recursively.
     *
     * @param array &$visited Map of role names to track circular dependencies.
     * @return Permission[]
     */
    public function getAllPermissions(array &$visited = []): array
    {
        if (isset($visited[$this->name])) {
            return [];
        }

        $visited[$this->name] = true;

        $permissions = [];
        foreach ($this->permissions as $p) {
            $key = ($p->namespace ?? '') . ':' . $p->name;
            $permissions[$key] = $p;
        }

        foreach ($this->parents as $parent) {
            foreach ($parent->getAllPermissions($visited) as $p) {
                $key = ($p->namespace ?? '') . ':' . $p->name;
                $permissions[$key] = $p;
            }
        }

        return array_values($permissions);
    }

    /**
     * Check if this role contains any of the given permissions.
     *
     * @param string ...$permissions Variable list of permission names.
     * @return bool
     */
    public function isPermitted(string ...$permissions): bool
    {
        $allPerms = $this->getAllPermissions();
        $perms = array_map(fn($p) => $p->name, $allPerms);

        foreach ($permissions as $p) {
            if (in_array($p, $perms)) {
                return true;
            }
        }

        return false;
    }
}

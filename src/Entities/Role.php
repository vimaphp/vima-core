<?php
declare(strict_types=1);

namespace Vima\Core\Entities;

class Role
{
    public function __construct(
        public string $name,
        /** @var Permission[] */
        public array $permissions = [],
        public ?string $description = null,
        public int|string|null $id = null,
    ) {
    }

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

    public function addPermission(Permission $permission): self
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }

        return $this;
    }

    public function removePermission(Permission $permission): self
    {
        $this->permissions = array_filter(
            $this->permissions,
            fn($p) => $p !== $permission
        );

        return $this;
    }

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

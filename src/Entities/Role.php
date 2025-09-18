<?php

namespace Vima\Core\Entities;

class Role
{
    public function __construct(
        private string $name,
        /** @var Permission[] */
        private array $permissions = [],
        private ?string $description = null,
    ) {
    }

    public static function define(string $name, array $permissions = [], ?string $description = null): self
    {
        $role = new self($name);

        foreach ($permissions as $perm) {
            $permission = $perm instanceof Permission ? $perm : new Permission($perm);
            $role->addPermission($permission);
        }

        $role->description = $description;

        return $role;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /** @return Permission[] */
    public function getPermissions(): array
    {
        return $this->permissions;
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
            $perms[] = $p->getName();
        }

        foreach ($permissions as $p) {
            if (in_array($p, $perms)) {
                return true;
            }
        }

        return false;
    }
}

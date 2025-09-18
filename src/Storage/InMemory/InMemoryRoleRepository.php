<?php

namespace Vima\Core\Storage\InMemory;

use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Entities\Role;

class InMemoryRoleRepository implements RoleRepositoryInterface
{
    /** @var Role[] */
    private array $roles = [];

    public function findByName(string $name): ?Role
    {
        return $this->roles[$name] ?? null;
    }

    public function save(Role $role): void
    {
        $this->roles[$role->getName()] = $role;
    }

    public function delete(Role $role): void
    {
        unset($this->roles[$role->getName()]);
    }

    public function all(): array
    {
        return $this->roles;
    }
}

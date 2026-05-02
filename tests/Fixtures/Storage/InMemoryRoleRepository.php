<?php

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Entities\Bare\BareRole;
use Vima\Core\Contracts\EventDispatcherInterface;

class InMemoryRoleRepository implements RoleRepositoryInterface
{
    /** @var BareRole[] */
    private array $roles = [];

    private int $id = 1;

    public function __construct(
        private ?EventDispatcherInterface $dispatcher = null
    ) {
    }

    public function findById(int|string $id): ?BareRole
    {
        foreach ($this->roles as $r) {
            if ($r->id == $id) {
                return $r;
            }
        }

        return null;
    }

    public function findByName(string $name, ?string $namespace = null): ?BareRole
    {
        $key = ($namespace ?? 'global') . ':' . $name;
        return $this->roles[$key] ?? null;
    }

    public function save(BareRole $role): BareRole
    {
        if (!$role->id) {
            $role->id = $this->id++;
        }

        $key = ($role->namespace ?? 'global') . ':' . $role->name;
        $this->roles[$key] = $role;

        return $role;
    }

    public function delete(BareRole $role): void
    {
        $key = ($role->namespace ?? 'global') . ':' . $role->name;
        unset($this->roles[$key]);
    }

    public function all(?string $namespace = null): array
    {
        $filtered = array_values($this->roles);

        if ($namespace !== null) {
            $filtered = array_filter($this->roles, fn($r) => $r->namespace === $namespace);
        }

        return array_values($filtered);
    }

    public function deleteAll(): void
    {
        $this->roles = [];
        $this->id = 1;
    }
}

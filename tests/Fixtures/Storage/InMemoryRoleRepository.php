<?php

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Entities\Role;
use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\Contracts\RoleParentRepositoryInterface;

use function Vima\Core\resolve;

class InMemoryRoleRepository implements RoleRepositoryInterface
{
    /** @var Role[] */
    private array $roles = [];

    private int $id = 1;

    public function __construct(
        private ?EventDispatcherInterface $dispatcher = null
    ) {
    }

    public function find(int|string $id): ?Role
    {
        return $this->findById($id);
    }

    public function findById(int|string $id): ?Role
    {
        $role = null;
        foreach ($this->roles as $r) {
            if ($r->id == $id) {
                $role = $r;
                break;
            }
        }

        if (!$role) {
            return null;
        }

        return $role;
    }

    public function findByName(string $name, ?string $namespace = null): ?Role
    {
        $key = $namespace . ':' . $name;
        return $this->roles[$key] ?? null;
    }

    public function save(Role $role): Role
    {
        if (!$role->id) {
            $role->id = $this->id++;
        }

        $key = $role->namespace . ':' . $role->name;
        $this->roles[$key] = $role;

        return $role;
    }

    public function delete(Role $role): void
    {
        $key = $role->namespace . ':' . $role->name;
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

    public function getChildren(Role $role): array
    {
        $parentRepo = resolve(RoleParentRepositoryInterface::class);
        return $parentRepo->getChildren($role);
    }

    public function getParents(Role $role): array
    {
        $parentRepo = resolve(RoleParentRepositoryInterface::class);
        return $parentRepo->getParents($role);
    }

    public function deleteAll(): void
    {
        $this->roles = [];
    }
}

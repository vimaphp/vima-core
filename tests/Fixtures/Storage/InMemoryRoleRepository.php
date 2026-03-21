<?php

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\RolePermission;
use Vima\Core\Events\Repository\RepositoryAction;
use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\Contracts\RoleParentRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Entities\RoleParent;

use function Vima\Core\resolve;

class InMemoryRoleRepository implements RoleRepositoryInterface
{
    /** @var Role[] */
    private array $roles = [];

    private int $id = 1;

    public function __construct(
        private ?EventDispatcherInterface $dispatcher = null
    ) {}

    public function find(int|string $id, bool $resolve = false): ?Role
    {
        return $this->findById($id, $resolve);
    }

    public function findById($id, bool $resolve = false): ?Role
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

        if ($resolve) {
            $role->permissions = [];
            $role->parents = [];

            /** @var RolePermissionRepositoryInterface $rpRepo */
            $rpRepo = resolve(RolePermissionRepositoryInterface::class);
            /** @var PermissionRepositoryInterface $pmRepo */
            $pmRepo = resolve(PermissionRepositoryInterface::class);

            $rps = $rpRepo->getRolePermissions($role);
            foreach ($rps as $rp) {
                if (isset($rp->permission_id)) {
                    $p = $pmRepo->findById($rp->permission_id);
                    if ($p) $role->permit($p);
                }
            }

            /** @var \Vima\Core\Contracts\RoleParentRepositoryInterface $hpRepo */
            $hpRepo = resolve(\Vima\Core\Contracts\RoleParentRepositoryInterface::class);
            $role->parents = $hpRepo->getParents($role);
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
        if ($role->id === null) {
            $role->id = $this->id;
            $this->id++;
            $this->dispatcher?->dispatch(new RepositoryAction(RepositoryAction::ACTION_CREATED, Role::class, $role));
        } else {
            $this->dispatcher?->dispatch(new RepositoryAction(RepositoryAction::ACTION_UPDATED, Role::class, $role));
        }

        $key = $role->namespace . ':' . $role->name;
        $this->roles[$key] = $role;

        // get role permission memory storage
        $rpMemory = resolve(RolePermissionRepositoryInterface::class);
        $pmMemory = resolve(PermissionRepositoryInterface::class);

        // add permssions to the role permission memory storage
        foreach ($role->permissions as $i => $pm) {
            $permission = $pmMemory->findByName($pm->name, $pm->namespace);

            if (!$permission) {
                $permission = $pmMemory->save($pm);
            }

            $role->permissions[$i] = $permission;
            $rpMemory->assign(RolePermission::define(
                role_id: $role->id,
                permission_id: $permission->id
            ));
        }

        // handle parents
        $rpRepo = resolve(RoleParentRepositoryInterface::class);
        foreach ($role->parents as $parent) {
             // Save parent first if needed
             if ($parent->id === null) {
                 $this->save($parent);
             }
             $rpRepo->assign(RoleParent::define(
                role_id: $role->id,
                parent_id: $parent->id
             ));
        }

        return $role;
    }

    public function delete(Role $role): void
    {
        $key = $role->namespace . ':' . $role->name;
        unset($this->roles[$key]);
    }

    public function all(?string $namespace = null, bool $onlyGlobal = false, bool $resolve = false): array
    {
        $filtered = array_values($this->roles);

        if ($namespace !== null) {
            $filtered = array_filter($this->roles, fn($r) => $r->namespace === $namespace);
        } elseif ($onlyGlobal) {
            $filtered = array_filter($this->roles, fn($r) => empty($r->namespace));
        }

        $parentRepo = resolve(RoleParentRepositoryInterface::class);

        if ($resolve) {
            foreach ($filtered as $role) {
                $role->parents = $parentRepo->getParents($role);
            }
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

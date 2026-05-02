<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Vima\Core\Services;

use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Entities\Permission;
use Vima\Core\Entities\Bare\BarePermission;
use Vima\Core\Events\Repository\RepositoryAction;
use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\Support\Utils;

use Vima\Core\Contracts\CacheInterface;

/**
 * Class PermissionManager
 * 
 * Internal service for managing Permission entities and user-specific permission assignments.
 *
 * @package Vima\Core\Services
 */
class PermissionManager
{
    public function __construct(
        private PermissionRepositoryInterface $permissions,
        private UserPermissionRepositoryInterface $userPermissions,
        private EventDispatcherInterface $dispatcher,
        private CacheInterface $cache,
    ) {
    }

    /**
     * Create and persist a new permission.
     *
     * @param string|Permission $name Permission name or instance.
     * @param string|null $description Optional description.
     * @return Permission
     */
    public function create(string|Permission $name, ?string $description = null, ?string $namespace = null): Permission
    {
        $permission = $name instanceof Permission ? $name : new Permission($name, namespace: $namespace);

        if ($description !== null) {
            $permission->description = $description;
        }

        return $this->save($permission);
    }

    /**
     * Find a permission by name or instance.
     *
     * @param string|Permission|BarePermission $permission
     * @return Permission|null
     */
    public function find(int|string|Permission|BarePermission $permission, ?string $namespace = null): ?Permission
    {
        $id = null;
        $name = null;

        if (is_int($permission)) {
            $id = $permission;
        } elseif (is_string($permission)) {
            [$resolvedNamespace, $name] = Utils::splitPermission($permission);
            $namespace = $resolvedNamespace ?? $namespace;
        } elseif ($permission instanceof BarePermission) {
            $id = $permission->id;
            $name = $permission->name;
            $namespace = $permission->namespace;
        } else {
            $id = $permission->id;
            $name = $permission->name;
            $namespace = $permission->namespace;
        }

        $barePermission = $id
            ? $this->permissions->findById($id)
            : $this->permissions->findByName((string) $name, $namespace);

        if (!$barePermission) {
            return null;
        }

        return new Permission(
            name: $barePermission->name,
            namespace: $barePermission->namespace,
            description: $barePermission->description,
            id: $barePermission->id
        );
    }

    /**
     * Returns the user-specific permissions assigned to the user (not via roles).
     * 
     * @param int|string $user_id
     * @return Permission[]
     */
    public function getDirectPermissions(int|string $user_id): array
    {
        $userPermissions = $this->userPermissions->findByUserId($user_id);

        $permissons = [];

        foreach ($userPermissions as $up) {
            $barePerm = $this->permissions->findById($up->permission_id);
            if ($barePerm) {
                $p = new Permission(
                    name: $barePerm->name,
                    namespace: $barePerm->namespace,
                    description: $barePerm->description,
                    id: $barePerm->id,
                    constraints: $up->constraints ?? []
                );
                $permissons[] = $p;
            }
        }

        return array_filter($permissons);
    }

    /**
     * Delete a permission.
     *
     * @param Permission $permission
     * @return void
     */
    public function delete(Permission $permission): void
    {
        $bare = new BarePermission(
            id: $permission->id,
            name: $permission->name,
            namespace: $permission->namespace,
            description: $permission->description
        );
        $this->permissions->delete($bare);
    }

    /**
     * Save/Update a permission entity.
     *
     * @param Permission $permission
     * @return Permission
     */
    public function save(Permission $permission): Permission
    {
        $existing = $this->find($permission);

        $bare = new BarePermission(
            id: $permission->id,
            name: $permission->name,
            namespace: $permission->namespace,
            description: $permission->description
        );

        $bare = $this->permissions->save($bare);

        $permission->id = $bare->id;

        if ($existing) {
            $this->dispatcher?->dispatch(new RepositoryAction(
                action: RepositoryAction::ACTION_UPDATED,
                entityClass: Permission::class,
                payload: $permission,
            ));
        } else {
            $this->dispatcher?->dispatch(new RepositoryAction(
                action: RepositoryAction::ACTION_CREATED,
                entityClass: Permission::class,
                payload: $permission,
            ));
        }

        return $permission;
    }

    /**
     * Retrieve all permissions.
     *
     * @return Permission[]
     */
    public function all(?string $namespace = null): array
    {
        $barePermissions = $this->permissions->all($namespace);
        return array_map(fn($bare) => new Permission(
            name: $bare->name,
            namespace: $bare->namespace,
            description: $bare->description,
            id: $bare->id
        ), $barePermissions);
    }

    public function findByName(string $name, ?string $namespace = null): ?Permission
    {
        return $this->find($name, $namespace);
    }

    public function deleteAll(): void
    {
        $this->permissions->deleteAll();
    }
}

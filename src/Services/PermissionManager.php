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
use Vima\Core\Exceptions\PermissionNotFoundException;
use function Vima\Core\resolve;

/**
 * Class PermissionManager
 * 
 * Internal service for managing Permission entities and user-specific permission assignments.
 *
 * @package Vima\Core\Services
 */
class PermissionManager
{
    private PermissionRepositoryInterface $permissions;
    private UserPermissionRepositoryInterface $userPermissions;

    public function __construct()
    {
        $this->permissions = resolve(PermissionRepositoryInterface::class);
        $this->userPermissions = resolve(UserPermissionRepositoryInterface::class);
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

        $permission->description = $description;

        return $this->permissions->save(permission: $permission);
    }

    /**
     * Find a permission by name or instance.
     *
     * @param string|Permission $permission
     * @return Permission|null
     * @throws PermissionNotFoundException
     */
    public function find(string|Permission $permission, ?string $namespace = null): ?Permission
    {
        $name = is_string($permission) ? $permission : $permission->name;
        $id = !is_string($permission) ? $permission->id : null;
        $permNamespace = !is_string($permission) ? $permission->namespace : $namespace;

        $permission = $id
            ? $this->permissions->findById($id)
            : $this->permissions->findByName($name, $permNamespace);

        if (!$permission) {
            throw new PermissionNotFoundException("Permission with the name [$name] not found");
        }

        return $permission;
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
            $permissons[] = $this->permissions->findById($up->permissionId ?? $up->permission_id);
        }

        return array_filter($permissons);
    }

    /**
     * Delete a permission.
     *
     * @param Permission $name
     * @return void
     */
    public function delete(Permission $name): void
    {
        $this->permissions->delete($name);
    }

    /**
     * Save/Update a permission entity.
     *
     * @param Permission $permission
     * @return Permission
     */
    public function save(Permission $permission): Permission
    {
        return $this->permissions->save($permission);
    }

    /**
     * Retrieve all permissions.
     *
     * @return Permission[]
     */
    public function all(?string $namespace = null): array
    {
        return $this->permissions->all($namespace);
    }
}

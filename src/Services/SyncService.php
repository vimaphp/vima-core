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

use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
use Vima\Core\Entities\Sync\Skipped;
use Vima\Core\Entities\Sync\SyncResponse;
use Vima\Core\Services\ConfigResolver;

/**
 * Class SyncService
 * 
 * Synchronizes declarative configuration (roles and permissions) into the persistent storage.
 *
 * @package Vima\Core\Services
 */
class SyncService
{
    private bool $refresh = false;

    /**
     * @param RoleRepositoryInterface $roles
     * @param PermissionRepositoryInterface $permissions
     * @param RolePermissionRepositoryInterface|null $rolePermissions
     */
    public function __construct(
        private RoleRepositoryInterface $roles,
        private PermissionRepositoryInterface $permissions,
        private ?RolePermissionRepositoryInterface $rolePermissions = null
    ) {
    }

    /**
     * Set whether to refresh the database before syncing.
     *
     * @param bool $refresh
     * @return $this
     */
    public function refresh(bool $refresh = true): self
    {
        $this->refresh = $refresh;
        return $this;
    }

    /**
     * Synchronizes the provided configuration into repositories.
     * 
     * It ensures all permissions exist first, then creates/updates roles 
     * and maps the permissions to them.
     *
     * @param VimaConfig $config The system configuration.
     * @return void
     */
    public function sync(VimaConfig $config): SyncResponse
    {
        if ($this->refresh) {
            $this->rolePermissions?->deleteAll();
            $this->roles->deleteAll();
            $this->permissions->deleteAll();
        }

        $skippedRoles = [];
        $skippedPermissions = [];

        // Validate & normalize using ConfigResolver
        $resolver = new ConfigResolver($config);

        // Sync permissions first
        foreach ($config->setup->permissions as $permission) {
            $existing = $this->permissions->findByName($permission->name);
            if ($existing) {
                $existing->description = $permission->description;
                $this->permissions->save($existing);
            } else {
                $this->permissions->save($permission);
            }
        }

        // Sync roles with resolved permissions
        $roles = $resolver->getRoles(); // [roleName => ['description' => ..., 'permissions' => [...]]]

        foreach ($roles as $roleName => $roleData) {
            $role = $this->roles->findByName($roleName);

            if ($role) {
                $role->description = $roleData['description'] ?? null;
                $role->permissions = []; // Reset to sync fresh
            } else {
                $role = new Role(
                    name: $roleName,
                    permissions: [],
                    description: $roleData['description'] ?? null
                );
            }

            foreach ($roleData['permissions'] as $permName) {
                $permission = $this->permissions->findByName($permName) ?? new Permission($permName);

                if (!$permission->id) {
                    $skippedPermissions[$permName] = "Included for role $roleName but not defined in permssions"; 
                }

                $role->permit($permission);
            }

            $this->roles->save($role);
        }

        $shouldWarn = !empty($skippedPermissions) || !empty($skippedRoles);

        return new SyncResponse(
            new Skipped(
                $skippedRoles,
                $skippedPermissions
            ),
            $shouldWarn
        );
    }
}

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
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
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
    /**
     * @param RoleRepositoryInterface $roles
     * @param PermissionRepositoryInterface $permissions
     */
    public function __construct(
        private RoleRepositoryInterface $roles,
        private PermissionRepositoryInterface $permissions
    ) {
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
    public function sync(VimaConfig $config): void
    {
        // Validate & normalize using ConfigResolver
        $resolver = new ConfigResolver($config);

        // Sync permissions first
        foreach ($config->setup->permissions as $permission) {
            $existing = $this->permissions->findByName($permission->name);
            if (!$existing) {
                $this->permissions->save($permission);
            }
        }

        // Sync roles with resolved permissions
        $roles = $resolver->getRoles(); // [roleName => ['description' => ..., 'permissions' => [...]]]

        foreach ($roles as $roleName => $roleData) {
            $role = $this->roles->findByName($roleName) ?? new Role(
                name: $roleName,
                permissions: [],
                description: $roleData['description'] ?? null
            );

            foreach ($roleData['permissions'] as $permName) {
                $permission = $this->permissions->findByName($permName) ?? new Permission($permName);
                $this->permissions->save($permission);

                $role->addPermission($permission);
            }

            $this->roles->save($role);
        }
    }
}

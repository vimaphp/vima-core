<?php

namespace Vima\Core\Services;

use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
use Vima\Core\Services\ConfigResolver;

class SyncService
{
    public function __construct(
        private RoleRepositoryInterface $roles,
        private PermissionRepositoryInterface $permissions
    ) {
    }

    /**
     * Syncs configuration array into repositories.
     *
     * @param array $config The raw config array (permissions & roles)
     */
    public function sync(array $config): void
    {
        // Validate & normalize using ConfigResolver
        $resolver = new ConfigResolver($config);

        // Sync permissions first
        foreach ($config['permissions'] as $permission) {
            // $permission is already a Permission instance (validated by resolver)
            $existing = $this->permissions->findByName($permission->getName());
            if (!$existing) {
                $this->permissions->save($permission);
            }
        }

        // Sync roles with resolved permissions
        $roles = $resolver->getRoles(); // [roleName => ['description' => ..., 'permissions' => [...]]]

        foreach ($roles as $roleName => $roleData) {
            $role = $this->roles->findByName($roleName) ?? new Role(
                $roleName,
                [],
                $roleData['description'] ?? null
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

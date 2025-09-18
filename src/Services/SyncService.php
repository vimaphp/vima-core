<?php

namespace Vima\Core\Services;

use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;

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
     * Example config:
     * [
     *   'roles' => [
     *      'admin' => ['create_post', 'delete_post'],
     *      'editor' => ['create_post']
     *   ]
     * ]
     */
    public function sync(array $config): void
    {
        foreach ($config['roles'] ?? [] as $roleName => $perms) {
            $role = $this->roles->findByName($roleName) ?? new Role($roleName);

            foreach ($perms as $permName) {
                $permission = $this->permissions->findByName($permName) ?? new Permission($permName);
                $this->permissions->save($permission);

                $role->addPermission($permission);
            }

            $this->roles->save($role);
        }
    }
}

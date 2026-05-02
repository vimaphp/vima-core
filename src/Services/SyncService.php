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
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
use Vima\Core\Entities\SuperAdmin;
use Vima\Core\Entities\Sync\Skipped;
use Vima\Core\Entities\Sync\SyncResponse;
use Vima\Core\Services\ConfigResolver;
use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\Events\Sync\SyncStarted;
use Vima\Core\Events\Sync\SyncFinished;
use Vima\Core\Events\DefaultEventDispatcher;
use Vima\Core\Contracts\CacheInterface;
use Vima\Core\Support\Utils;

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

    public function __construct(
        private RoleManager $roles,
        private PermissionManager $permissions,
        private ?RolePermissionRepositoryInterface $rolePermissions = null,
        private ?EventDispatcherInterface $dispatcher = null,
        private ?CacheInterface $cache = null
    ) {
        $this->dispatcher ??= new DefaultEventDispatcher();
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
        $this->dispatcher->dispatch(new SyncStarted($config, $this->refresh));

        if ($this->refresh) {
            $this->rolePermissions?->deleteAll();
            $this->roles->deleteAll();
            $this->permissions->deleteAll();
        }

        $skippedRoles = [];
        $skippedPermissions = [];

        $config = $this->setSuperAdminIntoConfigSetup($config);

        // Validate & normalize using ConfigResolver
        $resolver = new ConfigResolver($config);

        // Sync roles with resolved permissions
        $roles = $resolver->getRoles(); // [roleName => ['description' => ..., 'permissions' => [...]]]
        $syncedRoles = [];

        // Sync permissions first
        foreach ($config->setup->permissions as $permission) {
            [$permNamespace, $permName] = Utils::splitPermission($permission->name);
            $permNamespace ??= $permission->namespace;
            $existing = $this->permissions->findByName($permName, $permNamespace);
            if ($existing) {
                $existing->description = $permission->description;
                $existing->namespace = $permission->namespace;
                $this->permissions->save($existing);
            } else {
                $this->permissions->save($permission);
            }
        }

        foreach ($roles as $roleName => $roleData) {
            [$roleNamespace, $roleName] = Utils::splitPermission($roleName);
            $roleNamespace ??= $roleData['namespace'] ?? null;
            $role = $this->roles->findByName($roleName, $roleNamespace);
            $namespacedRoleName = $roleNamespace ? "{$roleNamespace}:{$roleName}" : $roleName;

            if ($role) {
                $role->description = $roleData['description'] ?? null;
                $role->namespace = $roleNamespace;
                $role->parents = $roleData['parents'] ?? [];
                $role->children = $roleData['children'] ?? [];

                if (!in_array($namespacedRoleName, $syncedRoles)) {
                    $role->permissions = []; // reset for resync
                }
            } else {
                $role = new Role(
                    name: $roleName,
                    namespace: $roleNamespace,
                    permissions: [],
                    description: $roleData['description'] ?? null,
                    parents: $roleData['parents'] ?? [],
                    children: $roleData['children'] ?? []
                );
            }

            foreach ($roleData['permissions'] as $namespacedPermName) {
                [$permNamespace, $permName] = Utils::splitPermission($namespacedPermName);
                $permission = $this->permissions->findByName($permName, $permNamespace);

                if (!$permission) {
                    $permission = new Permission(name: $permName, namespace: $permNamespace);
                    $this->permissions->save($permission);
                }

                $role->permit($permission);
            }

            $this->roles->save($role);

            $syncedRoles[] = $namespacedRoleName;
        }

        $shouldWarn = !empty($skippedPermissions) || !empty($skippedRoles);

        $response = new SyncResponse(
            new Skipped(
                $skippedRoles,
                $skippedPermissions
            ),
            $shouldWarn
        );

        $this->dispatcher->dispatch(new SyncFinished($response));

        $this->cache?->clear();

        return $response;
    }

    private function getSuperAdminRole(VimaConfig $config): ?SuperAdmin
    {

        if ($config->superAdminRole) {
            if ($config->superAdminRole instanceof SuperAdmin) {
                $superAdmin = $config->superAdminRole;

                $superAdmin->permissions = ['*']; // Ensure super admin has all permissions

                return $superAdmin;
            }

            [$namespace, $name] = Utils::splitPermission($config->superAdminRole);

            return new SuperAdmin(
                name: $name,
                namespace: $namespace,
                description: 'Super Admin role with all permissions',
                permissions: [
                    new Permission(name: '*')
                ]
            );
        }

        return null;
    }

    private function getConfigSuperAdminRoleInSetup(VimaConfig $config): ?Role
    {

        $superAdminRole = $this->getSuperAdminRole($config);

        if (!$superAdminRole) {
            return null;
        }

        foreach ($config->setup->roles as $role) {
            if ($role->getFullName() === $superAdminRole->getFullName()) {
                return $role;
            }
        }

        return null;
    }

    public function setSuperAdminIntoConfigSetup(VimaConfig $config): VimaConfig
    {

        $superAdminInConfig = $this->getConfigSuperAdminRoleInSetup($config);
        $definedSuperAdmin = $this->getSuperAdminRole($config);

        if (!$superAdminInConfig && $definedSuperAdmin) {
            $config->setup->roles[] = $definedSuperAdmin;
        }

        if ($superAdminInConfig) {
            if (!in_array('*', $superAdminInConfig->permissions)) {
                $superAdminInConfig->permissions[] = '*';
            }

            $key = array_filter($config->setup->roles, fn($r) => $r->getFullName() === $superAdminInConfig->getFullName()) |> array_key_first(...);

            unset($config->setup->roles[$key]);
            $config->setup->roles[] = $superAdminInConfig;

            $config->setup->roles = array_filter($config->setup->roles) |> array_values(...);
        }

        return $config;
    }
}

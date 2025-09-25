<?php

namespace Vima\Core\Support;

use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Services\PolicyRegistry;

class FrameworkIntegration
{
    /**
     * Tables required by Vima.
     *
     * @return array<string, string> [alias => default table name]
     */
    public static function requiredTables(): array
    {
        return [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'role_permission' => 'role_permission',
            'user_roles' => 'user_roles',
        ];
    }

    /**
     * Columns required for each table.
     */
    public static function requiredColumns(): array
    {
        return [
            'roles' => [
                'id' => 'id',
                'name' => 'name',
                'description' => 'description',
            ],
            'permissions' => [
                'id' => 'id',
                'name' => 'name',
                'description' => 'description',
            ],
            'role_permission' => [
                'role_id' => 'role_id',
                'permission_id' => 'permission_id',
            ],
            'user_roles' => [
                'user_id' => 'user_id',
                'role_id' => 'role_id',
            ],
        ];
    }

    /**
     * Repositories needed for full integration.
     *
     * @return array<string, string>
     */
    public static function repositoryContracts(): array
    {
        return [
            'roles' => RoleRepositoryInterface::class,
            'permissions' => PermissionRepositoryInterface::class,
        ];
    }

    /**
     * Provides helper functions a framework can wire up.
     */
    public static function helpers(): array
    {
        return [
            'vima()' => 'Returns AccessManager service instance',
            'can()' => 'Check if a user has a given permission',
            'isRole()' => 'Check if a user has a given role',
        ];
    }

    /**
     * Returns a registry instance for policies.
     */
    public static function policyRegistry(): PolicyRegistry
    {
        return PolicyRegistry::instance(); // Singleton/shared instance
    }
}

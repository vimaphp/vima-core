<?php

namespace Vima\Core\Support;

use Vima\Core\Config\Columns;
use Vima\Core\Config\Tables;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Services\PolicyRegistry;

class FrameworkIntegration
{
    /**
     * Tables required by Vima.
     *
     * @return Tables
     */
    public static function requiredTables(): Tables
    {
        return (new VimaConfig())->tables;
    }

    /**
     * Columns required for each table.
     */
    public static function requiredColumns(): Columns
    {
        return (new VimaConfig())->columns;
    }

    /**
     * Repositories needed for full integration.
     *
     * @return object<string, class-string>
     */
    public static function repositoryContracts(): object
    {
        return (object) [
            'roles' => RoleRepositoryInterface::class,
            'permissions' => PermissionRepositoryInterface::class,
        ];
    }

    /**
     * Provides helper functions a framework can wire up.
     */
    public static function helpers(): object
    {
        return (object) [
            'vima' => 'Returns AccessManager service instance',
            'can' => 'Check if a user has a given permission',
        ];
    }

    /**
     * Returns a registry instance for policies.
     */
    public static function policyRegistry(): PolicyRegistry
    {
        return PolicyRegistry::instance();
    }
}

<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Vima\Core\Support;

use Vima\Core\Config\Columns;
use Vima\Core\Config\Tables;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Schema\Schema;
use Vima\Core\Schema\Table;
use Vima\Core\Schema\Field;
use Vima\Core\Schema\ForeignKey;

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
     * Define internal application table schema requirements dynamically.
     * Useful for building dynamic database migrations/setups in integrating
     * frameworks without hardcoding field definitions mapping them 
     * exactly to configured values.
     * 
     * @return Schema
     */
    public static function getSchema(): Schema
    {
        $tables = self::requiredTables();
        $cols = self::requiredColumns();

        $schema = new Schema();

        $rolesTable = (new Table($tables->roles))
            ->addField(new Field('id', 'integer', unsigned: true, autoIncrement: true))
            ->addField(new Field($cols->roles->name, 'string', length: 100))
            ->addField(new Field($cols->roles->description, 'text', nullable: true))
            ->addField(new Field($cols->roles->namespace, 'string', length: 100, nullable: true))
            ->addField(new Field($cols->roles->context, 'json', nullable: true))
            ->addField(new Field('created_at', 'datetime', nullable: true))
            ->addField(new Field('updated_at', 'datetime', nullable: true))
            ->addPrimaryKey('id')
            ->addUniqueKey([$cols->roles->namespace, $cols->roles->name]);

        $permissionsTable = (new Table($tables->permissions))
            ->addField(new Field('id', 'integer', unsigned: true, autoIncrement: true))
            ->addField(new Field($cols->permissions->name, 'string', length: 100))
            ->addField(new Field($cols->permissions->description, 'text', nullable: true))
            ->addField(new Field($cols->permissions->namespace, 'string', length: 100, nullable: true))
            ->addField(new Field('created_at', 'datetime', nullable: true))
            ->addField(new Field('updated_at', 'datetime', nullable: true))
            ->addPrimaryKey('id')
            ->addUniqueKey([$cols->permissions->namespace, $cols->permissions->name]);

        $rolePermissionsTable = (new Table($tables->rolePermissions))
            ->addField(new Field('id', 'integer', unsigned: true, autoIncrement: true))
            ->addField(new Field($cols->rolePermissions->roleId, 'integer', unsigned: true))
            ->addField(new Field($cols->rolePermissions->permissionId, 'integer', unsigned: true))
            ->addField(new Field($cols->rolePermissions->constraints, 'json', nullable: true))
            ->addPrimaryKey('id')
            ->addForeignKey(new ForeignKey($cols->rolePermissions->roleId, $tables->roles, 'id', 'CASCADE', 'CASCADE'))
            ->addForeignKey(new ForeignKey($cols->rolePermissions->permissionId, $tables->permissions, 'id', 'CASCADE', 'CASCADE'));

        $userRolesTable = (new Table($tables->userRoles))
            ->addField(new Field('id', 'integer', unsigned: true, autoIncrement: true))
            ->addField(new Field($cols->userRoles->userId, 'string', length: 50))
            ->addField(new Field($cols->userRoles->roleId, 'integer', unsigned: true))
            ->addPrimaryKey('id')
            ->addForeignKey(new ForeignKey($cols->userRoles->roleId, $tables->roles, 'id', 'CASCADE', 'CASCADE'));

        $userPermissionsTable = (new Table($tables->userPermissions))
            ->addField(new Field('id', 'integer', unsigned: true, autoIncrement: true))
            ->addField(new Field($cols->userPermissions->userId, 'string', length: 50))
            ->addField(new Field($cols->userPermissions->permissionId, 'integer', unsigned: true))
            ->addField(new Field($cols->userPermissions->constraints, 'json', nullable: true))
            ->addPrimaryKey('id')
            ->addForeignKey(new ForeignKey($cols->userPermissions->permissionId, $tables->permissions, 'id', 'CASCADE', 'CASCADE'));

        $roleParentsTable = (new Table($tables->roleParents))
            ->addField(new Field('id', 'integer', unsigned: true, autoIncrement: true))
            ->addField(new Field($cols->roleParents->roleId, 'integer', unsigned: true))
            ->addField(new Field($cols->roleParents->parentId, 'integer', unsigned: true))
            ->addPrimaryKey('id')
            ->addForeignKey(new ForeignKey($cols->roleParents->roleId, $tables->roles, 'id', 'CASCADE', 'CASCADE'))
            ->addForeignKey(new ForeignKey($cols->roleParents->parentId, $tables->roles, 'id', 'CASCADE', 'CASCADE'));

        $userDeniesTable = (new Table($tables->userDenies))
            ->addField(new Field('id', 'integer', unsigned: true, autoIncrement: true))
            ->addField(new Field($cols->userDenies->userId, 'string', length: 50))
            ->addField(new Field($cols->userDenies->permissionId, 'integer', unsigned: true))
            ->addField(new Field($cols->userDenies->reason, 'text', nullable: true))
            ->addField(new Field($cols->userDenies->expiresAt, 'datetime', nullable: true))
            ->addField(new Field('created_at', 'datetime', nullable: true))
            ->addPrimaryKey('id')
            ->addUniqueKey([$cols->userDenies->userId, $cols->userDenies->permissionId])
            ->addForeignKey(new ForeignKey($cols->userDenies->permissionId, $tables->permissions, 'id', 'CASCADE', 'CASCADE'));

        $userRoleDeniesTable = (new Table($tables->userRoleDenies))
            ->addField(new Field('id', 'integer', unsigned: true, autoIncrement: true))
            ->addField(new Field($cols->userRoleDenies->userId, 'string', length: 50))
            ->addField(new Field($cols->userRoleDenies->roleId, 'integer', unsigned: true))
            ->addField(new Field($cols->userRoleDenies->reason, 'text', nullable: true))
            ->addField(new Field($cols->userRoleDenies->expiresAt, 'datetime', nullable: true))
            ->addField(new Field('created_at', 'datetime', nullable: true))
            ->addPrimaryKey('id')
            ->addUniqueKey([$cols->userRoleDenies->userId, $cols->userRoleDenies->roleId])
            ->addForeignKey(new ForeignKey($cols->userRoleDenies->roleId, $tables->roles, 'id', 'CASCADE', 'CASCADE'));

        $auditLogsTable = (new Table($tables->auditLogs))
            ->addField(new Field('id', 'integer', unsigned: true, autoIncrement: true))
            ->addField(new Field($cols->auditLogs->userId, 'string', length: 50, nullable: true))
            ->addField(new Field($cols->auditLogs->permission, 'string', length: 100))
            ->addField(new Field($cols->auditLogs->namespace, 'string', length: 100, nullable: true))
            ->addField(new Field($cols->auditLogs->result, 'integer')) // 1 for allow, 0 for deny
            ->addField(new Field($cols->auditLogs->reason, 'text', nullable: true))
            ->addField(new Field($cols->auditLogs->arguments, 'text', nullable: true)) // JSON encoded
            ->addField(new Field('created_at', 'datetime', nullable: true))
            ->addPrimaryKey('id');

        $schema->addTable($rolesTable)
            ->addTable($permissionsTable)
            ->addTable($rolePermissionsTable)
            ->addTable($userRolesTable)
            ->addTable($userPermissionsTable)
            ->addTable($roleParentsTable)
            ->addTable($userDeniesTable)
            ->addTable($userRoleDeniesTable)
            ->addTable($auditLogsTable);

        return $schema;
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

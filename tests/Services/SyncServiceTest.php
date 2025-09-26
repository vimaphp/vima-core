<?php

use Vima\Core\Config\Columns;
use Vima\Core\Config\Models;
use Vima\Core\Config\PermissionColumns;
use Vima\Core\Config\RoleColumns;
use Vima\Core\Config\RolePermissionColumns;
use Vima\Core\Config\Setup;
use Vima\Core\Config\Tables;
use Vima\Core\Config\UserMethods;
use Vima\Core\Config\UserRoleColumns;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Entities\{Permission, Role};
use Vima\Core\Services\SyncService;
use Vima\Core\Storage\InMemory\InMemoryPermissionRepository;
use Vima\Core\Storage\InMemory\InMemoryRoleRepository;

beforeEach(function () {
    /** @var \Vima\Core\Tests\ConfigResolverTestCase $this */
    $this->permissions = [
        Permission::define('users.manage', 'Manage users'),
        Permission::define('users.view', 'View users'),
        Permission::define('blogs.create', 'Create blogs'),
        Permission::define('blogs.edit', 'Edit blogs'),
    ];

    $this->roles = [
        Role::define(
            'admin',
            ['*'],
            'Admin role'
        ),
        Role::define(
            'editor',
            ['blogs.*'],
            'Editor role'
        ),
        Role::define(
            'viewer',
            ['users.view'],
            'Viewer role'
        ),
    ];

    $this->config = new VimaConfig(
        tables: new Tables(),
        setup: new Setup(
            roles: $this->roles,
            permissions: $this->permissions,
        ),
        models: new Models(
            roles: "",
            permissions: "",
        ),
        columns: new Columns(
            roles: new RoleColumns(),
            permissions: new PermissionColumns(),
            userRoles: new UserRoleColumns(),
            rolePermission: new RolePermissionColumns()
        ),
        userMethods: new UserMethods(),
    );
});

test('', function () {
    /** @var \Vima\Core\Tests\ConfigResolverTestCase $this */
    $roleRepo = new InMemoryRoleRepository();
    $permRepo = new InMemoryPermissionRepository();

    $syncService = new SyncService(
        roles: $roleRepo,
        permissions: $permRepo
    );

    $syncService->sync($this->config);

    $roles = $roleRepo->all();

    expect($roles)->toHaveCount(3);
});

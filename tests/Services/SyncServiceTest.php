<?php

use Vima\Core\Config\Setup;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Entities\{Permission, Role};
use Vima\Core\Exceptions\ConfigResolverExcpetion;
use Vima\Core\Exceptions\InvalidConfigException;
use Vima\Core\Services\SyncService;
use Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository;
use function Vima\Core\resolve;

beforeEach(function () {
    initDependencies();

    /** @var \Vima\Core\Tests\ConfigResolverTestCase $this */

    $this->permissions = [
        Permission::define('users.manage', 'Manage users'),
        Permission::define('users.view', 'View users'),
        Permission::define('blogs.create', 'Create blogs'),
        Permission::define('blogs.edit', 'Edit blogs'),

        // namepsaced permissions
        Permission::define('invoice.create', 'Create invoices', 'owner'),
        Permission::define('invoice.edit', 'Edit invoices', 'owner'),
        Permission::define('invoice.manage', 'Manage invoices', 'owner'),
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
            ['users.view', 'nm:tool.*'],
            'Viewer role'
        ),

        // with namespaces
        Role::define(
            'owner',
            ['blogs.*'],
            'Blog owner',
            'blog'
        ),

        // can we sync a role with namespace attached to the name
        Role::define(
            'blog:owner',
            [
                'owner:invoice.*',
                'nm:tool.use' // should be automatically created as a new permission
            ],
        ),
    ];

    $this->config = new VimaConfig(
        setup: new Setup(
            roles: $this->roles,
            permissions: $this->permissions,
        ),
    );
});

test('Syncs permissions and roles successfully', function () {
    /** @var \Vima\Core\Tests\ConfigResolverTestCase $this */
    $syncService = resolve(SyncService::class);
    $syncService->sync($this->config);

    /** @var RoleRepositoryInterface */
    $roleRepo = resolve(RoleRepositoryInterface::class);
    /** @var PermissionRepositoryInterface */
    $permRepo = resolve(PermissionRepositoryInterface::class);

    $roles = $roleRepo->all(null, false, true);
    $perms = $permRepo->all();

    expect($roles)->toHaveCount(4);
    expect($perms)->toHaveCount(8);

    $ownerRole = array_filter($roles, fn(Role $role) => $role->name === 'owner') |> array_values(...);
    $adminRole = array_filter($roles, fn(Role $role) => $role->name === 'admin') |> array_values(...);
    $viewerRole = array_filter($roles, fn(Role $role) => $role->name === 'viewer') |> array_values(...);

    expect($ownerRole)->toHaveCount(1);
    expect($adminRole[0]->permissions)->toHaveCount(8);
    expect($viewerRole[0]->permissions)->toHaveCount(2);
});

test('Throws an excpetion for invalid permssion wildcard', function () {
    $this->roles[1] = Role::define(
        'editor',
        ['blogs.*', 'eejejifefj.*'],
        'Editor role'
    );

    $this->config = new VimaConfig(
        setup: new Setup(
            roles: $this->roles,
            permissions: $this->permissions,
        ),
    );

    // dd($this->config);

    $syncService = resolve(SyncService::class);

    expect(fn() => $syncService->sync($this->config))->toThrow(ConfigResolverExcpetion::class, "No permission match for wildcard 'eejejifefj.*' was found. Ensure a fully defined permission for it exists either as a Vima\Core\Entities\Permission object or as a string in a role permissions definition.");
});


test('Throws an exception for invalid permssion format', function () {
    $this->roles[1] = Role::define(
        'editor',
        ['blogs.*', 'suerff. fri ifr '],
        'Editor role'
    );

    $this->config = new VimaConfig(
        setup: new Setup(
            roles: $this->roles,
            permissions: $this->permissions,
        ),
    );

    $syncService = resolve(SyncService::class);

    expect(fn() => $syncService->sync($this->config))->toThrow(InvalidConfigException::class, "Invalid permission name given 'suerff. fri ifr ' for role 'editor'");
});
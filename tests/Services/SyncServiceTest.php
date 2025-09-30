<?php

use Vima\Core\Config\Setup;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Entities\{Permission, Role};
use Vima\Core\Services\AccessManager;
use Vima\Core\Services\PermissionManager;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Services\SyncService;
use Vima\Core\Services\UserResolver;
use Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository;
use function Vima\Core\registerMany;
use function Vima\Core\resolve;

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
        setup: new Setup(
            roles: $this->roles,
            permissions: $this->permissions,
        ),
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

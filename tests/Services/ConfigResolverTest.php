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
use Vima\Core\Services\{ConfigResolver, ConfigSerializer};

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

test('valid config resolves permissions and roles', function () {
    /** @var \Vima\Core\Tests\ConfigResolverTestCase $this */

    $resolver = new ConfigResolver($this->config);

    expect($resolver->getPermissions())->toContain('users.manage', 'blogs.edit', 'users.view', 'blogs.edit');

    $roles = $resolver->getRoles();

    expect($roles)->toHaveKey('admin');
    expect($roles['admin']['permissions'])->toContain('users.manage', 'blogs.edit', 'users.view', 'blogs.edit');
    expect($roles['admin']['permissions'])->toHaveCount(4);

    expect($roles)->toHaveKey('editor');
    expect($roles['editor']['permissions'])->toContain('blogs.create', 'blogs.edit');
    expect($roles['editor']['permissions'])->toHaveCount(2);


    expect($roles)->toHaveKey('viewer');
    expect($roles['viewer']['permissions'])->toBe(['users.view']);
    expect($roles['viewer']['permissions'])->toHaveCount(1);
});


test('serializer outputs valid array and json', function () {
    /** @var \Vima\Core\Tests\ConfigResolverTestCase $this */

    $resolver = new ConfigResolver($this->config);
    $serializer = new ConfigSerializer();

    $array = $serializer->toArray($resolver);
    expect($array)->toHaveKeys(['permissions', 'roles']);
    expect($array['roles']['editor']['permissions'])->toContain('blogs.edit');

    $json = $serializer->toJson($resolver);
    expect($json)->toBeJson();
    expect($json)->toContain('users.manage');
});

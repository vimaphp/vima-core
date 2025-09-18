<?php

use Vima\Core\Entities\{Permission, Role};
use Vima\Core\Services\{ConfigResolver, ConfigSerializer};
use Vima\Core\Exceptions\InvalidConfigException;

beforeEach(function () {
    /** @var \Tests\ConfigResolverTestCase $this */
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

    $this->config = [
        'permissions' => $this->permissions,
        'roles' => $this->roles,
    ];
});

test('valid config resolves permissions and roles', function () {
    /** @var \Tests\ConfigResolverTestCase $this */

    $resolver = new ConfigResolver($this->config);

    expect($resolver->getPermissions())->toContain('users.manage', 'blogs.edit');

    $roles = $resolver->getRoles();

    expect($roles)->toHaveKey('admin');
    expect($roles['admin']['permissions'])->toContain('users.manage', 'blogs.edit');

    expect($roles)->toHaveKey('editor');
    expect($roles['editor']['permissions'])->toContain('blogs.create', 'blogs.edit');

    expect($roles)->toHaveKey('viewer');
    expect($roles['viewer']['permissions'])->toBe(['users.view']);
});

test('throws if permissions key is missing', function () {
    /** @var \Tests\ConfigResolverTestCase $this */

    new ConfigResolver(['roles' => $this->roles]);
})->throws(InvalidConfigException::class);

test('throws if roles key is missing', function () {
    /** @var \Tests\ConfigResolverTestCase $this */

    new ConfigResolver(['permissions' => $this->permissions]);
})->throws(InvalidConfigException::class);

test('throws if permissions contain invalid objects', function () {
    /** @var \Tests\ConfigResolverTestCase $this */

    $badConfig = [
        'permissions' => ['not-a-permission'],
        'roles' => $this->roles,
    ];

    new ConfigResolver($badConfig);
})->throws(InvalidConfigException::class);

test('throws if roles contain invalid objects', function () {
    /** @var \Tests\ConfigResolverTestCase $this */

    $badConfig = [
        'permissions' => $this->permissions,
        'roles' => ['not-a-role'],
    ];

    new ConfigResolver($badConfig);
})->throws(InvalidConfigException::class);

test('serializer outputs valid array and json', function () {
    /** @var \Tests\ConfigResolverTestCase $this */

    $resolver = new ConfigResolver($this->config);
    $serializer = new ConfigSerializer();

    $array = $serializer->toArray($resolver);
    expect($array)->toHaveKeys(['permissions', 'roles']);
    expect($array['roles']['editor']['permissions'])->toContain('blogs.edit');

    $json = $serializer->toJson($resolver);
    expect($json)->toBeJson();
    expect($json)->toContain('users.manage');
});

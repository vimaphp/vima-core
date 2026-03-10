<?php

use Vima\Core\Entities\{Permission, Role};

it('can add and check permissions', function () {
    $role = new Role('admin');
    $perm = new Permission('users.delete');

    $role->permit($perm);

    expect($role->isPermitted('users.delete'))->toBeTrue()
        ->and($role->isPermitted('users.edit'))->toBeFalse();
});

it('returns role name correctly', function () {
    $role = new Role('editor');
    expect($role->name)->toBe('editor');
});

it('returns permissions list', function () {
    $role = new Role('viewer');
    $role->permit(new Permission('posts.view'));

    expect($role->permissions)
        ->toBeArray()
        ->sequence(
            function ($perm) {
                /** @var Permission $perm->value */
                expect($perm->value->name)->toBe('posts.view');
            }
        );
});

it('removes a permission', function () {
    $role = new Role("author");
    $permission = new Permission("blog.create");

    $role->permit($permission);

    expect($role->isPermitted('blog.create'))->toBeTrue();

    $role->forbid($permission);

    expect($role->isPermitted('blog.create'))->toBeFalse();
});

it('defines a role with string permissions', function () {
    $role = Role::define('editor', ['posts.create', 'posts.edit']);

    expect($role->name)->toBe('editor')
        ->and($role->isPermitted('posts.create'))->toBeTrue()
        ->and($role->isPermitted('posts.edit'))->toBeTrue();
});

it('defines a role with Permission objects', function () {
    $p1 = Permission::define('users.view');
    $p2 = Permission::define('users.delete');

    $role = Role::define('admin', [$p1, $p2]);

    expect($role->isPermitted('users.view'))->toBeTrue()
        ->and($role->isPermitted('users.delete'))->toBeTrue();
});

it('defines a role with no permissions', function () {
    $role = Role::define('guest');

    expect($role->name)->toBe('guest')
        ->and($role->permissions)->toBeArray()->toHaveCount(0);
});

it('can save and delete itself via the AccessManager', function () {
    $roleRepo = new \Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository();
    $permissionRepo = new \Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository();
    $userPermissionRepo = new \Vima\Core\Tests\Fixtures\Storage\InMemoryUserPermissionRepository();
    $userRoleRepo = new \Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository();
    $rolePermissionRepo = new \Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository();

    // Register all dependencies in the global container
    \Vima\Core\registerMany([
        \Vima\Core\Contracts\RoleRepositoryInterface::class => $roleRepo,
        \Vima\Core\Contracts\PermissionRepositoryInterface::class => $permissionRepo,
        \Vima\Core\Contracts\UserPermissionRepositoryInterface::class => $userPermissionRepo,
        \Vima\Core\Contracts\UserRoleRepositoryInterface::class => $userRoleRepo,
        \Vima\Core\Contracts\RolePermissionRepositoryInterface::class => $rolePermissionRepo,
        \Vima\Core\Services\UserResolver::class => new \Vima\Core\Services\UserResolver(new \Vima\Core\Config\VimaConfig()),
        \Vima\Core\Services\PolicyRegistry::class => new \Vima\Core\Services\PolicyRegistry(),
        \Vima\Core\Contracts\AccessManagerInterface::class => \Vima\Core\Services\AccessManager::class,
    ]);

    $role = new Role('active-record-role');

    // Save
    $savedRole = $role->save();
    expect($savedRole->id)->not->toBeNull();
    expect($roleRepo->findByName('active-record-role'))->not->toBeNull();

    // Delete
    $role->delete();
    expect($roleRepo->findByName('active-record-role'))->toBeNull();
});

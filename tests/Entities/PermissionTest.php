<?php

use Vima\Core\Entities\Permission;

it('creates a permission with resource.action format', function () {
    $permission = new Permission(name: 'posts.create', description: 'user can create a post');

    expect($permission->name)->toBe('posts.create');
    expect($permission->description)->toBe('user can create a post');
});

it('can save itself via the AccessManager', function () {
    $permissionRepo = new \Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository();

    // Register dependencies in the global container
    \Vima\Core\registerMany([
        \Vima\Core\Contracts\RoleRepositoryInterface::class => new \Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository(),
        \Vima\Core\Contracts\PermissionRepositoryInterface::class => $permissionRepo,
        \Vima\Core\Contracts\UserPermissionRepositoryInterface::class => new \Vima\Core\Tests\Fixtures\Storage\InMemoryUserPermissionRepository(),
        \Vima\Core\Contracts\UserRoleRepositoryInterface::class => new \Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository(),
        \Vima\Core\Contracts\RolePermissionRepositoryInterface::class => new \Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository(),
        \Vima\Core\Services\UserResolver::class => new \Vima\Core\Services\UserResolver(new \Vima\Core\Config\VimaConfig()),
        \Vima\Core\Services\PolicyRegistry::class => new \Vima\Core\Services\PolicyRegistry(),
        \Vima\Core\Contracts\AccessManagerInterface::class => \Vima\Core\Services\AccessManager::class,
    ]);

    $permission = new Permission('active-record-perm');

    // Save
    $savedPerm = $permission->save();
    expect($savedPerm->id)->not->toBeNull();
    expect($permissionRepo->findByName('active-record-perm'))->not->toBeNull();
});

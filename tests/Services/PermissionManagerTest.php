<?php

use Vima\Core\Config\VimaConfig;
use Vima\Core\DependencyContainer;
use Vima\Core\Entities\Permission;
use Vima\Core\Exceptions\PermissionNotFoundException;
use Vima\Core\Services\PermissionManager;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Services\UserResolver;
use Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository;

beforeEach(function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $this->permissionRepo = new InMemoryPermissionRepository();

    new DependencyContainer(
        roles: new InMemoryRoleRepository(),
        permissions: $this->permissionRepo,
        userPermissions: new InMemoryUserPermissionRepository(),
        userRoles: new InMemoryUserRoleRepository(),
        rolePermissions: new InMemoryRolePermissionRepository(),
        userResolver: new UserResolver(new VimaConfig()),
        policies: new PolicyRegistry(),
    );

    $this->permissionManager = new PermissionManager();
});

it('creates a permission', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $perm = $this->permissionManager->create('posts.delete');

    expect($perm)->toBeInstanceOf(Permission::class)
        ->and($perm->name)->toBe('posts.delete');
});

it('compares permissions equality by name', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $a = $this->permissionManager->create('posts.edit');
    $b = $this->permissionManager->create('posts.edit');

    expect($a->name)->toBe($b->name);
});

it('throws exception if permission not found', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    expect($this->permissionManager->find("posts.create"));
})->throws(PermissionNotFoundException::class);

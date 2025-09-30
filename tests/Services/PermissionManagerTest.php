<?php

use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Entities\Permission;
use Vima\Core\Exceptions\PermissionNotFoundException;
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

beforeEach(function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $this->permissionRepo = new InMemoryPermissionRepository();

    registerMany([
        RoleRepositoryInterface::class => new InMemoryRoleRepository(),
        PermissionRepositoryInterface::class => $this->permissionRepo,
        UserPermissionRepositoryInterface::class => new InMemoryUserPermissionRepository(),
        UserRoleRepositoryInterface::class => new InMemoryUserRoleRepository(),
        RolePermissionRepositoryInterface::class => new InMemoryRolePermissionRepository(),
        UserResolver::class => new UserResolver(new VimaConfig()),
        PolicyRegistry::class => new PolicyRegistry(),
        AccessManager::class,
        SyncService::class => fn(DependencyContainer $c) => new SyncService(
            roles: $c->get(RoleRepositoryInterface::class),
            permissions: $c->get(PermissionRepositoryInterface::class)
        )
    ]);

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

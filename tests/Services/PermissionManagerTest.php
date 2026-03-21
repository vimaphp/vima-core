<?php

use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\CacheInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\PolicyRegistryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Entities\Permission;
use Vima\Core\Exceptions\PermissionNotFoundException;
use Vima\Core\Services\AccessManager;
use Vima\Core\Services\NullCache;
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
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    registerMany([
        RoleRepositoryInterface::class => InMemoryRoleRepository::class,
        PermissionRepositoryInterface::class => InMemoryPermissionRepository::class,
        UserPermissionRepositoryInterface::class => InMemoryUserPermissionRepository::class,
        UserRoleRepositoryInterface::class => InMemoryUserRoleRepository::class,
        RolePermissionRepositoryInterface::class => InMemoryRolePermissionRepository::class,
        PolicyRegistryInterface::class => PolicyRegistry::class,
        CacheInterface::class => NullCache::class,
        UserResolver::class,
        AccessManager::class,
        SyncService::class,
        PermissionManager::class,
    ]);

    $this->permissionManager = resolve(PermissionManager::class);
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

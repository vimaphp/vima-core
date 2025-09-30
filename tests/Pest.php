<?php

use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\DependencyContainer;
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

arch()->preset()->strict();

beforeEach(function () {
    registerMany([
        RoleRepositoryInterface::class => new InMemoryRoleRepository(),
        UserPermissionRepositoryInterface::class => new InMemoryUserPermissionRepository(),
        UserRoleRepositoryInterface::class => new InMemoryUserRoleRepository(),
        RolePermissionRepositoryInterface::class => new InMemoryRolePermissionRepository(),
        UserResolver::class => new UserResolver(new VimaConfig()),
        PolicyRegistry::class => new PolicyRegistry(),
        PermissionRepositoryInterface::class => new InMemoryPermissionRepository(),
        AccessManager::class,
        PermissionManager::class,
        SyncService::class => fn(DependencyContainer $c) => new SyncService(
            roles: $c->get(RoleRepositoryInterface::class),
            permissions: $c->get(PermissionRepositoryInterface::class)
        )
    ]);
});

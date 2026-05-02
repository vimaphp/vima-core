<?php

use Vima\Core\Contracts\AccessManagerInterface;
use Vima\Core\Contracts\CacheInterface;
use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\Contracts\UserDenyRepositoryInterface;
use Vima\Core\Contracts\UserRoleDenyRepositoryInterface;
use Vima\Core\Contracts\UserInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Services\AccessManager;
use Vima\Core\Contracts\PolicyRegistryInterface;
use Vima\Core\Services\NullCache;
use Vima\Core\Services\RoleManager;
use Vima\Core\Services\PermissionManager;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Services\UserResolver;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Tests\Fixtures\MockEventDispatcher;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleParentRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserDenyRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleDenyRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserPermissionRepository;
use Vima\Core\Contracts\UserRepositoryInterface;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRepository;
use function Vima\Core\registerMany;

function initDependencies()
{
    DependencyContainer::reset();

    registerMany([
        UserRepositoryInterface::class => new InMemoryUserRepository(),
        RoleRepositoryInterface::class => new InMemoryRoleRepository(),
        PermissionRepositoryInterface::class => new InMemoryPermissionRepository(),
        RolePermissionRepositoryInterface::class => new InMemoryRolePermissionRepository(),
        UserRoleRepositoryInterface::class => new InMemoryUserRoleRepository(),
        UserPermissionRepositoryInterface::class => new InMemoryUserPermissionRepository(),
        RoleParentRepositoryInterface::class => new InMemoryRoleParentRepository(),
        UserDenyRepositoryInterface::class => new InMemoryUserDenyRepository(),
        UserRoleDenyRepositoryInterface::class => new InMemoryUserRoleDenyRepository(),
        PolicyRegistryInterface::class => new PolicyRegistry(),
        CacheInterface::class => new NullCache(),
        AccessManagerInterface::class => AccessManager::class,
        EventDispatcherInterface::class => new MockEventDispatcher(),
        UserResolver::class,
        RoleManager::class,
        PermissionManager::class,
    ]);
}

class UserMock implements UserInterface
{
    public function __construct(public int|string $id)
    {
    }
    public function vimaGetId(): int|string
    {
        return $this->id;
    }
    public function vimaGetRoles(): array
    {
        return [];
    }
}

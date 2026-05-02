<?php

use Vima\Core\Contracts\CacheInterface;
use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\PolicyRegistryInterface;
use Vima\Core\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserDenyRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Contracts\UserRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Services\AccessManager;
use Vima\Core\Contracts\AccessManagerInterface;
use Vima\Core\Services\NullCache;
use Vima\Core\Services\PermissionManager;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Services\RoleManager;
use Vima\Core\Services\UserResolver;
use Vima\Core\Tests\Fixtures\MockEventDispatcher;
use Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleParentRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserDenyRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository;

it('resets the container', function () {
    $container = DependencyContainer::getInstance();
    $container->register('test', (object) ['foo' => 'bar']);

    DependencyContainer::reset();
    $newContainer = DependencyContainer::getInstance();

    expect(fn() => $newContainer->get('test'))->toThrow(Exception::class);
});

it('auto-wires AccessManager', function () {
    // initDependencies registers many things, including AccessManager::class
    initDependencies();
    $container = DependencyContainer::getInstance();

    $manager = $container->get(AccessManagerInterface::class);
    expect($manager)->toBeInstanceOf(AccessManager::class);
});

it('registers many with numeric keys', function () {
    $container = DependencyContainer::getInstance();
    $container->registerMany([
        AccessManager::class
    ]);

    expect($container->get(AccessManager::class))->toBeInstanceOf(AccessManager::class);
});

it('throws exception if concrete is not provided for interface with no default', function () {
    DependencyContainer::reset();
    $container = DependencyContainer::getInstance();

    expect(fn() => $container->get('NonExistentInterface'))
        ->toThrow(RuntimeException::class);
});

it('throws exception for circular dependencies', function () {
    class CircularA
    {
        public function __construct(CircularB $b)
        {
        }
    }
    class CircularB
    {
        public function __construct(CircularA $a)
        {
        }
    }

    $container = DependencyContainer::getInstance();
    expect(fn() => $container->get(CircularA::class))
        ->toThrow(RuntimeException::class, 'Circular dependency detected');
});

it('throws exception for non-instantiable classes', function () {
    abstract class AbstractClass
    {
    }
    $container = DependencyContainer::getInstance();
    expect(fn() => $container->get(AbstractClass::class))
        ->toThrow(RuntimeException::class, 'no concrete implementation registered');
});

it('throws exception for unresolvable primitive parameters', function () {
    class Unresolvable
    {
        public function __construct(string $name)
        {
        }
    }
    $container = DependencyContainer::getInstance();
    expect(fn() => $container->get(Unresolvable::class))
        ->toThrow(RuntimeException::class, 'Cannot resolve parameter $name');
});

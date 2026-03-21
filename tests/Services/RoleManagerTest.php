<?php

use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Exceptions\RoleNotFoundException;
use Vima\Core\Services\AccessManager;
use Vima\Core\Services\PermissionManager;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Services\RoleManager;
use Vima\Core\Services\SyncService;
use Vima\Core\Services\UserResolver;
use Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleParentRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository;
use Vima\Core\Tests\Fixtures\MockEventDispatcher;
use Vima\Core\Events\Repository\RepositoryAction;
use function Vima\Core\{registerMany, resolve};

beforeEach(function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $this->dispatcher = new MockEventDispatcher();
    $this->roleRepo = new InMemoryRoleRepository($this->dispatcher);

    registerMany([
        RoleRepositoryInterface::class => $this->roleRepo,
        UserPermissionRepositoryInterface::class => new InMemoryUserPermissionRepository(),
        UserRoleRepositoryInterface::class => new InMemoryUserRoleRepository(),
        RolePermissionRepositoryInterface::class => new InMemoryRolePermissionRepository(),
        RoleParentRepositoryInterface::class => new InMemoryRoleParentRepository(),
        EventDispatcherInterface::class => $this->dispatcher,
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

    // create a bunch of users
    foreach ([new Role('admin'), new Role('user')] as $user) {
        $this->roleRepo->save($user);
    };

    $this->roleManager = new RoleManager(
        $this->roleRepo,
        resolve(UserRoleRepositoryInterface::class),
        resolve(RolePermissionRepositoryInterface::class),
        $this->dispatcher
    );
});

it('creates a role with permissions', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this   */

    /* One way to create a role with permissions */
    /* $role = $this->roleManager->create('editor');

    foreach ([new Permission('posts.create'), new Permission('posts.edit')] as $p) {
        $role->permit($p);
    }

    $this->roleManager->save($role);
 */
    /* Second way to create a role with permissions */
    $role = new Role(name: 'editor', permissions: [new Permission(name: 'posts.create'), new Permission(name: 'posts.edit')]);

    $this->roleManager->create($role);

    $role = $this->roleManager->find("editor");

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->isPermitted('posts.create'))->toBeTrue()
        ->and($role->isPermitted('posts.edit'))->toBeTrue();
});

it('finds role by name', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $this->roleManager->create('admin');
    $role = $this->roleManager->find('admin');
    $permission = new Permission('users.delete');
    $role->permit($permission);

    $this->roleManager->save($role);

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->isPermitted('users.delete'))->toBeTrue();
});

it('throws exception if role not found', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    expect($this->roleManager->find('ghost'));
})->throws(RoleNotFoundException::class);

it('supports role inheritance', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $admin = new Role(name: 'admin', permissions: [new Permission('admin.access')]);
    $editor = new Role(name: 'editor', permissions: [new Permission('posts.edit')]);
    $editor->inherit($admin);

    $this->roleManager->save($admin);
    $this->roleManager->save($editor);

    $role = $this->roleManager->find('editor', resolve: true);

    expect($role->parents)->toHaveCount(1)
        ->and($role->parents[0]->name)->toBe('admin')
        ->and($role->isPermitted('posts.edit'))->toBeTrue()
        ->and($role->isPermitted('admin.access'))->toBeTrue();
});

it('dispatches events', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $this->dispatcher->dispatched = []; // Clear events from beforeEach
    $this->roleManager->create('new-role');

    expect($this->dispatcher->dispatched)->toHaveCount(1)
        ->and($this->dispatcher->dispatched[0])->toBeInstanceOf(RepositoryAction::class)
        ->and($this->dispatcher->dispatched[0]->action)->toBe(RepositoryAction::ACTION_CREATED);
});

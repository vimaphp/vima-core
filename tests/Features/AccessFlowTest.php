<?php

use Vima\Core\Config\Setup;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\PolicyRegistryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Exceptions\PolicyNotFoundException;
use Vima\Core\Services\SyncService;
use Vima\Core\Services\UserResolver;
use Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository;
use Vima\Core\Services\AccessManager;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Events\DefaultEventDispatcher;
use Vima\Core\Tests\Fixtures\User;
use Vima\Core\Tests\Fixtures\MockEventDispatcher;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleParentRepository;
use function Vima\Core\registerMany;
use function Vima\Core\resolve;

beforeEach(function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */
    initDependencies();

    // Setup roles and permissions
    $adminRole = Role::define(name: 'admin', permissions: [
        Permission::define(name: 'posts.create'),
        Permission::define(name: 'posts.update'),
        Permission::define(name: 'posts.delete'),
        Permission::define(name: 'posts.view'),
    ]);

    $editorRole = Role::define(name: 'editor', permissions: [
        Permission::define(name: 'posts.create'),
        Permission::define(name: 'posts.update'),
        Permission::define(name: 'posts.view'),
    ]);

    $viewerRole = Role::define(name: 'viewer', permissions: [
        Permission::define(name: 'posts.view'),
    ]);

    $this->roles = [$adminRole, $editorRole, $viewerRole];

    $this->permissions = array_merge(
        $adminRole->permissions,
        $editorRole->permissions,
        $viewerRole->permissions
    );

    $setup = resolve(Setup::class);
    $setup->roles = $this->roles;
    $setup->permissions = $this->permissions;

    // Setup Policy Registry
    $this->policyRegistry = resolve(PolicyRegistryInterface::class);
    $this->policyRegistry->register('posts.update', function (User $user, $post) {
        // Editors and Admins can update any post
        foreach (resolve(AccessManager::class)->getUserRoles($user) as $role) {
            if (in_array($role->name, ['editor', 'admin'])) {
                return true;
            }
        }
        return false;
    });

    $this->manager = resolve(AccessManager::class);

    // two way to start off here
    // Both should work well

    // 1. Use the SyncSevice
    $config = resolve(VimaConfig::class);
    resolve(SyncService::class)->sync($config);

    // 2. Add the roles using the manager->addRole
    foreach ($this->roles as $role) {
        $this->manager->ensureRole($role);
    }

    // Fake users
    $this->alice = new User(1);
    $this->manager->assignRole($this->alice, $adminRole);

    $this->bob = new User(2);
    $this->manager->assignRole($this->bob, $editorRole);

    $this->carol = new User(3);
    $this->manager->assignRole($this->carol, $viewerRole);

    // Fake post resource
    $this->post = ['id' => 1, 'owner' => 3];
});

test('admins can update posts', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->isPermitted($this->alice, 'posts.update'))->toBeTrue();
    expect($this->manager->evaluatePolicy($this->alice, 'posts.update', null, $this->post))->toBeTrue();
});

test('editors can update posts', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->isPermitted($this->bob, 'posts.update'))->toBeTrue();
    expect($this->manager->evaluatePolicy($this->bob, 'posts.update', null, $this->post))->toBeTrue();
});

test('viewers cannot update posts, even if owner', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->isPermitted($this->carol, 'posts.update'))->toBeFalse();
    expect($this->manager->evaluatePolicy($this->carol, 'posts.update', null, $this->post))->toBeFalse();
    $this->manager->enforce($this->carol, 'posts.update'); // should throw
})->throws(AccessDeniedException::class);

test('admins can update posts using can', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->alice, 'posts.update', null, $this->post))->toBeTrue();
});

test('editors can update posts using can', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->bob, 'posts.update', null, $this->post))->toBeTrue();
});

test('viewers cannot update posts using can', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->carol, 'posts.update', null, $this->post))->toBeFalse();
});

test('viewers can view posts using can', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->carol, 'posts.view'))->toBeTrue();
    expect($this->manager->can($this->bob, 'posts.view'))->toBeTrue();
    expect($this->manager->can($this->alice, 'posts.view'))->toBeTrue();
});

test('returns false when user lacks permission even if policy exists', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->carol, 'posts.update', null, $this->post))->toBeFalse();
});

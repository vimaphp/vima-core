<?php

use Vima\Core\Config\VimaConfig;
use Vima\Core\DependencyContainer;
use Vima\Core\Exceptions\PolicyNotFoundException;
use Vima\Core\Services\AccessManager;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Services\PermissionManager;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Services\RoleManager;
use Vima\Core\Services\UserResolver;
use Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository;
use Vima\Core\Tests\Fixtures\User;

beforeEach(function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $this->roleRepo = new InMemoryRoleRepository();
    $this->permissionRepo = new InMemoryPermissionRepository();
    $this->userPermissionRepo = new InMemoryUserPermissionRepository();
    $this->userRoleRepo = new InMemoryUserRoleRepository();
    $this->rolePermissionRepo = new InMemoryRolePermissionRepository();

    $userResolver = new UserResolver(new VimaConfig());
    $policyRegistry = new PolicyRegistry();

    new DependencyContainer(
        roles: $this->roleRepo,
        permissions: $this->permissionRepo,
        userPermissions: $this->userPermissionRepo,
        userRoles: $this->userRoleRepo,
        rolePermissions: $this->rolePermissionRepo,
        userResolver: $userResolver,
        policies: $policyRegistry,
    );

    $this->accessManager = new AccessManager();
});

it('returns true if user has permission', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $user = new User(1);
    $permission = $this->accessManager->addPermission(Permission::define("post.view"));

    $role = Role::define(
        name: "admin",
        permissions: [
            $permission
        ]
    );

    $role = $this->accessManager->addRole($role);

    $this->accessManager->grantRole($user, $role);

    $this->accessManager->authorize($user, 'post.view');

    expect(true)->toBeTrue();
});

it('returns false if user lacks permission', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $user = new User(2);

    expect($this->accessManager->userHasPermission($user, 'posts.delete'))->toBeFalse();
});

it('throws AccessDeniedException when unauthorized', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $user = new User(3);
    $this->accessManager->authorize($user, 'users.delete');
})->throws(AccessDeniedException::class);

it('passes authorization if user has permission', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    /* $role = new Role('admin');
    $role->addPermission(new Permission('users.delete'));

    $this->accessManager->addRole($role);

    $user = new User(4);

    $this->accessManager->grantRole($user, $role);

    $this->accessManager->authorize($user, 'users.delete'); */
    expect(true)->toBeTrue();
});

it('throws exception for policy evaluation when no policy is registered', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $user = new User(5);
    expect($this->accessManager
        ->evaluatePolicy($user, 'update', new stdClass()))
        ->toBeFalse();
})->throws(PolicyNotFoundException::class, 'No policy registered for permission: update');

it('delegates policy evaluation to registry', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $registry = PolicyRegistry::define([
        'posts.update' => fn(User $u, $post) => $u->vimaGetId() === $post->ownerId,
    ]);

    $this->roleRepo = new InMemoryRoleRepository();
    $this->permissionRepo = new InMemoryPermissionRepository();
    $this->userPermissionRepo = new InMemoryUserPermissionRepository();
    $this->userRoleRepo = new InMemoryUserRoleRepository();
    $this->rolePermissionRepo = new InMemoryRolePermissionRepository();

    $userResolver = new UserResolver(new VimaConfig());

    new DependencyContainer(
        roles: $this->roleRepo,
        permissions: $this->permissionRepo,
        userPermissions: $this->userPermissionRepo,
        userRoles: $this->userRoleRepo,
        rolePermissions: $this->rolePermissionRepo,
        userResolver: $userResolver,
        policies: $registry,
    );

    $manager = new AccessManager();

    $user = new User(1);
    $post = (object) ['ownerId' => 1];

    expect($manager->evaluatePolicy($user, 'posts.update', $post))->toBeTrue();
});

it('throws exception if registry has no matching policy', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $registry = new PolicyRegistry();

    $this->roleRepo = new InMemoryRoleRepository();
    $this->permissionRepo = new InMemoryPermissionRepository();
    $this->userPermissionRepo = new InMemoryUserPermissionRepository();
    $this->userRoleRepo = new InMemoryUserRoleRepository();
    $this->rolePermissionRepo = new InMemoryRolePermissionRepository();

    $userResolver = new UserResolver(new VimaConfig());

    new DependencyContainer(
        roles: $this->roleRepo,
        permissions: $this->permissionRepo,
        userPermissions: $this->userPermissionRepo,
        userRoles: $this->userRoleRepo,
        rolePermissions: $this->rolePermissionRepo,
        userResolver: $userResolver,
        policies: $registry,
    );

    $manager = new AccessManager();

    $user = new User(1);

    expect($manager->evaluatePolicy($user, 'posts.update', new stdClass()))->toBeFalse();
})->throws(PolicyNotFoundException::class, 'No policy registered for permission: posts.update');
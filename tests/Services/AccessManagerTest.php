<?php

use Vima\Core\Exceptions\PolicyNotFoundException;
use Vima\Core\Services\AccessManager;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Storage\InMemory\InMemoryPermissionRepository;
use Vima\Core\Storage\InMemory\InMemoryRoleRepository;
use Vima\Core\Tests\Fixtures\User;

beforeEach(function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $this->roleRepo = new InMemoryRoleRepository();
    $this->permissionRepo = new InMemoryPermissionRepository();
    $this->accessManager = new AccessManager($this->roleRepo, $this->permissionRepo);
});

it('returns true if user has permission', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $role = new Role('editor');
    $role->addPermission(new Permission('posts.create'));

    $user = new User(1);
    $user->addRole($role);

    expect($this->accessManager->userHasPermission($user, 'posts.create'))->toBeTrue();
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

    $role = new Role('admin');
    $role->addPermission(new Permission('users.delete'));

    $user = new User(4);
    $user->addRole($role);

    $this->accessManager->authorize($user, 'users.delete'); // should not throw
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
    $registry = PolicyRegistry::define([
        'posts.update' => fn(User $u, $post) => $u->vimaGetId() === $post->ownerId,
    ]);

    $manager = new AccessManager(
        new InMemoryRoleRepository(),
        new InMemoryPermissionRepository(),
        $registry
    );

    $user = new User(1);
    $post = (object) ['ownerId' => 1];

    expect($manager->evaluatePolicy($user, 'posts.update', $post))->toBeTrue();
});

it('throws exception if registry has no matching policy', function () {
    $registry = new PolicyRegistry();
    $manager = new AccessManager(
        new InMemoryRoleRepository(),
        new InMemoryPermissionRepository(),
        $registry
    );

    $user = new User(1);

    expect($manager->evaluatePolicy($user, 'posts.update', new stdClass()))->toBeFalse();
})->throws(PolicyNotFoundException::class, 'No policy registered for permission: posts.update');

it('throws exception if no registry is provided', function () {
    $manager = new AccessManager(
        new InMemoryRoleRepository(),
        new InMemoryPermissionRepository(),
        null
    );

    $user = new User(1);

    expect($manager->evaluatePolicy($user, 'posts.update', new stdClass()))->toBeFalse();
})->throws(PolicyNotFoundException::class, 'No policy registered for permission: posts.update');

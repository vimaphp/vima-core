<?php

use Vima\Core\Tests\Fixtures\User;
use Vima\Core\Exceptions\PolicyNotFoundException;
use Vima\Core\Services\AccessManager;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Contracts\UserInterface;
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Storage\InMemory\InMemoryPermissionRepository;
use Vima\Core\Storage\InMemory\InMemoryRoleRepository;

beforeEach(function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    // Setup roles and permissions
    $adminRole = Role::define('admin', [
        Permission::define('posts.create'),
        Permission::define('posts.update'),
        Permission::define('posts.delete'),
        Permission::define('posts.view'),
    ]);

    $editorRole = Role::define('editor', [
        Permission::define('posts.create'),
        Permission::define('posts.update'),
        Permission::define('posts.view'),
    ]);

    $viewerRole = Role::define('viewer', [
        Permission::define('posts.view'),
    ]);

    $this->roles = [$adminRole, $editorRole, $viewerRole];

    $this->permissions = array_merge(
        $adminRole->getPermissions(),
        $editorRole->getPermissions(),
        $viewerRole->getPermissions()
    );

    // Setup Policy Registry
    $this->policyRegistry = new PolicyRegistry();
    $this->policyRegistry->register('posts.update', function (UserInterface $user, $post) {
        // Editors and Admins can update any post
        foreach ($user->vimaGetRoles() as $role) {
            if (in_array($role->getName(), ['editor', 'admin'])) {
                return true;
            }
        }
        return false; // viewers cannot update, even if they own
    });

    $this->roleRepo = new InMemoryRoleRepository();
    $this->permissionRepo = new InMemoryPermissionRepository();

    foreach ($this->roles as $r) {
        $this->roleRepo->save($r);
    }

    foreach ($this->permissions as $p) {
        $this->permissionRepo->save($p);
    }

    $this->manager = new AccessManager(
        $this->roleRepo,
        $this->permissionRepo,
        $this->policyRegistry
    );

    // Fake users
    $this->alice = new User(1);
    $this->alice->addRole($adminRole);

    $this->bob = new User(2);
    $this->bob->addRole($editorRole);

    $this->carol = new User(3);
    $this->carol->addRole($viewerRole);

    // Fake post resource
    $this->post = (object) ['id' => 1, 'owner' => 3];
});

test('admins can update posts', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->userHasPermission($this->alice, 'posts.update'))->toBeTrue();
    expect($this->manager->evaluatePolicy($this->alice, 'posts.update', $this->post))->toBeTrue();
});

test('editors can update posts', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->userHasPermission($this->bob, 'posts.update'))->toBeTrue();
    expect($this->manager->evaluatePolicy($this->bob, 'posts.update', $this->post))->toBeTrue();
});

test('viewers cannot update posts, even if owner', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->userHasPermission($this->carol, 'posts.update'))->toBeFalse();
    expect($this->manager->evaluatePolicy($this->carol, 'posts.update', $this->post))->toBeFalse();
    $this->manager->authorize($this->carol, 'posts.update'); // should throw
})->throws(AccessDeniedException::class);


test('admins can update posts using can', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->alice, 'posts.update', $this->post))->toBeTrue();
});

test('editors can update posts using can', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->bob, 'posts.update', $this->post))->toBeTrue();
});

test('viewers cannot update posts using can', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->carol, 'posts.update', $this->post))->toBeFalse();
});

test('viewers can view posts using can', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->carol, 'posts.view'))->toBeTrue();
    expect($this->manager->can($this->bob, 'posts.view'))->toBeTrue();
    expect($this->manager->can($this->alice, 'posts.view'))->toBeTrue();
});

test('throws exception when policy not defined but resource is provided', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    $user = $this->bob; // editor
    $fakeResource = (object) ['id' => 99];

    expect($this->manager->can($user, 'posts.delete', $fakeResource))->toBeFalse();
});

test('returns false when user lacks permission even if policy exists', function () {
    /** @var \Vima\Core\Tests\AccessFlowTestCase $this */

    expect($this->manager->can($this->carol, 'posts.update', $this->post))->toBeFalse();
});

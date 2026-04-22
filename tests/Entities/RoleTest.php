<?php

use Vima\Core\Contracts\AccessManagerInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Entities\{Permission, Role};
use function Vima\Core\resolve;

beforeEach(function () {
    // Clear any existing roles and permissions before each test
    initDependencies();
});

it('can add and check permissions', function () {
    $role = new Role('admin');
    $perm = new Permission('users.delete');

    $role->permit($perm)->save();

    expect($role->isPermitted('users.delete'))->toBeTrue()
        ->and($role->isPermitted('users.edit'))->toBeFalse();
});

it('returns role name correctly', function () {
    $role = new Role('editor');
    expect($role->name)->toBe('editor');
});

it('returns permissions list', function () {
    $role = new Role('viewer');
    $role->permit(new Permission('posts.view'));

    expect($role->permissions)
        ->toBeArray()
        ->sequence(
            function ($perm) {
                /** @var Permission $perm */
                expect($perm->value->name)->toBe('posts.view');
            }
        );
});

it('removes a permission', function () {
    $role = new Role("author");
    $permission = new Permission("blog.create");

    $role->permit($permission)->save();

    expect($role->isPermitted('blog.create'))->toBeTrue();

    $role->forbid($permission)->save();

    expect($role->isPermitted('blog.create'))->toBeFalse();
});

it('defines a role with string permissions', function () {
    $role = Role::define('editor', ['posts.create', 'posts.edit'])->save();

    expect($role->name)->toBe('editor')
        ->and($role->isPermitted('posts.create'))->toBeTrue()
        ->and($role->isPermitted('posts.edit'))->toBeTrue();
});

it('defines a role with Permission objects', function () {
    $p1 = Permission::define('users.view');
    $p2 = Permission::define('users.delete');

    $role = Role::define('admin', [$p1, $p2])->save();

    expect($role->isPermitted('users.view'))->toBeTrue()
        ->and($role->isPermitted('users.delete'))->toBeTrue();
});

it('defines a role with no permissions', function () {
    $role = Role::define('guest');

    expect($role->name)->toBe('guest')
        ->and($role->permissions)->toBeArray()->toHaveCount(0);
});

it('can save and delete itself via the AccessManager', function () {
    initDependencies();

    $role = new Role('active-record-role');
    $manager = resolve(AccessManagerInterface::class);

    $roleRepo = resolve(RoleRepositoryInterface::class);
    // Save
    $savedRole = $manager->updateRole($role);

    expect($savedRole->id)->not->toBeNull();
    expect($manager->getRole('active-record-role'))->not->toBeNull();

    // Delete
    $manager->deleteRole($role);
    expect($manager->getRole('active-record-role'))->toBeNull();
});

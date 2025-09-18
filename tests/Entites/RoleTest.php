<?php

use Vima\Core\Entities\{Permission, Role};

it('can add and check permissions', function () {
    $role = new Role('admin');
    $perm = new Permission('users.delete');

    $role->addPermission($perm);

    expect($role->hasPermission('users.delete'))->toBeTrue()
        ->and($role->hasPermission('users.edit'))->toBeFalse();
});

it('returns role name correctly', function () {
    $role = new Role('editor');
    expect($role->getName())->toBe('editor');
});

it('returns permissions list', function () {
    $role = new Role('viewer');
    $role->addPermission(new Permission('posts.view'));

    expect($role->getPermissions())
        ->toBeArray()
        ->sequence(
            function ($perm) {
                /** @var Permission $perm->value */
                expect($perm->value->getName())->toBe('posts.view');
            }
        );
});

it('removes a permission', function () {
    $role = new Role("author");
    $permission = new Permission("blog.create");

    $role->addPermission($permission);

    expect($role->hasPermission('blog.create'))->toBeTrue();

    $role->removePermission($permission);

    expect($role->hasPermission('blog.create'))->toBeFalse();
});

it('defines a role with string permissions', function () {
    $role = Role::define('editor', ['posts.create', 'posts.edit']);

    expect($role->getName())->toBe('editor')
        ->and($role->hasPermission('posts.create'))->toBeTrue()
        ->and($role->hasPermission('posts.edit'))->toBeTrue();
});

it('defines a role with Permission objects', function () {
    $p1 = Permission::define('users.view');
    $p2 = Permission::define('users.delete');

    $role = Role::define('admin', [$p1, $p2]);

    expect($role->hasPermission('users.view'))->toBeTrue()
        ->and($role->hasPermission('users.delete'))->toBeTrue();
});

it('defines a role with no permissions', function () {
    $role = Role::define('guest');

    expect($role->getName())->toBe('guest')
        ->and($role->getPermissions())->toBeArray()->toHaveCount(0);
});

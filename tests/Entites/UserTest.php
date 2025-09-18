<?php

use Vima\Core\Entities\{User, Role, Permission};

it('returns user id', function () {
    $user = new User(99);
    expect($user->getId())->toBe(99);
});

it('inherits permissions from roles', function () {
    $role = new Role('editor');
    $role->addPermission(new Permission('posts.create'));

    $user = new User(1);
    $user->addRole($role);

    expect($user->hasPermission('posts.create'))->toBeTrue()
        ->and($user->hasPermission('posts.delete'))->toBeFalse();
});

it('returns all assigned roles', function () {
    $role1 = new Role('editor');
    $role2 = new Role('viewer');

    $user = new User(2);
    $user->addRole($role1);
    $user->addRole($role2);

    $roles = $user->getRoles();

    expect($roles)->toHaveCount(2)
        ->and($roles[0]->getName())->toBe('editor')
        ->and($roles[1]->getName())->toBe('viewer');
});

it('removes an assigned role', function () {
    $role = new Role('editor');

    $user = new User(2);
    $user->addRole($role);

    $roles = $user->getRoles();

    expect($roles)->toHaveCount(1)
        ->and($roles[0]->getName())->toBe('editor');

    $user->removeRole($role);

    expect($user->getRoles())->toHaveCount(0);
});

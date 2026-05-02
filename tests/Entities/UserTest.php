<?php

use Vima\Core\Entities\User;
use Vima\Core\Entities\VimaIdentity;
use Vima\Core\Entities\Role;
use Vima\Core\Contracts\AccessManagerInterface;
use function Vima\Core\resolve;

beforeEach(function () {
    initDependencies();
});

it('can be instantiated with an id', function () {
    $user = new User(1);
    expect($user->id)->toBe(1);

    $user = User::define('user-123');
    expect($user->id)->toBe('user-123');
});

it('can be converted to VimaIdentity', function () {
    $user = new User(1);
    $identity = $user->toVimaIdentity();

    expect($identity)->toBeInstanceOf(VimaIdentity::class);
    expect($identity->id)->toBe(1);
});

it('can check roles', function () {
    $user = new User(1);
    
    $manager = resolve(AccessManagerInterface::class);
    $role = Role::define('admin');
    $role->save();
    $manager->assignRole(new UserMock(1), 'admin');

    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->hasRole('editor'))->toBeFalse();
});

it('can check permissions', function () {
    $user = new User(1);
    $manager = resolve(AccessManagerInterface::class);
    
    $role = Role::define('editor', ['posts.edit']);
    $role->save();
    
    $manager->assignRole(new UserMock(1), 'editor');

    expect($user->can('posts.edit'))->toBeTrue();
    expect($user->can('posts.delete'))->toBeFalse();
});

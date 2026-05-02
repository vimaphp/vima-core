<?php

use Vima\Core\Entities\VimaIdentity;
use Vima\Core\Entities\Role;
use Vima\Core\Contracts\AccessManagerInterface;
use function Vima\Core\resolve;

beforeEach(function () {
    initDependencies();
});

it('can be instantiated and defined', function () {
    $roles = [new Role('admin')];
    $identity = new VimaIdentity(1, $roles);
    
    expect($identity->id)->toBe(1);
    expect($identity->roles)->toBe($roles);

    $identity = VimaIdentity::define('user-1', $roles);
    expect($identity->id)->toBe('user-1');
    expect($identity->roles)->toBe($roles);
});

it('can check if it has a role', function () {
    $identity = VimaIdentity::define(1, [new Role('admin'), new Role('editor')]);

    expect($identity->hasRole('admin'))->toBeTrue();
    expect($identity->hasRole('editor'))->toBeTrue();
    expect($identity->hasRole('viewer'))->toBeFalse();
});

it('delegates can() to AccessManager', function () {
    $manager = resolve(AccessManagerInterface::class);
    
    $role = new Role('editor');
    $role->permit('posts.edit');
    $role->save();
    
    $identity = VimaIdentity::define(1, [$role]);
    
    // We need to make sure the manager knows about this user's roles
    $manager->assignRole(new UserMock(1), 'editor');

    expect($identity->can('posts.edit'))->toBeTrue();
    expect($identity->can('posts.delete'))->toBeFalse();
});

it('delegates isDenied() to AccessManager', function () {
    $manager = resolve(AccessManagerInterface::class);
    
    $manager->deny(new UserMock(1), 'posts.delete', 'Security risk');
    
    $identity = VimaIdentity::define(1);
    
    expect($identity->isDenied('posts.delete'))->toBeTrue();
    expect($identity->isDenied('posts.edit'))->toBeFalse();
});

it('can reconcile access via save()', function () {
    $manager = resolve(AccessManagerInterface::class);
    $role = Role::define('manager');
    $role->save();
    
    $identity = VimaIdentity::define(1, [$role]);
    $identity->save(); // Should call reconcileAccess

    $userRoles = $manager->getUserRoles(new UserMock(1));
    expect($userRoles)->toHaveCount(1);
    expect($userRoles[0]->name)->toBe('manager');
});

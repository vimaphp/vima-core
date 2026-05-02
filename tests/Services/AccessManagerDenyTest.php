<?php

use Vima\Core\Services\AccessManager;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
use Vima\Core\Tests\Fixtures\User;
use function Vima\Core\resolve;

beforeEach(function () {
    initDependencies();
});

it('denies access via exact permission match', function () {
    $manager = resolve(AccessManager::class);
    $user = new User(1);
    
    $manager->addPermission('posts.edit');
    $manager->permit($user, 'posts.edit');
    
    expect($manager->can($user, 'posts.edit'))->toBeTrue();
    
    $manager->deny($user, 'posts.edit');
    expect($manager->can($user, 'posts.edit'))->toBeFalse();
});

it('denies access via namespace wildcard (*)', function () {
    $manager = resolve(AccessManager::class);
    $user = new User(1);
    
    $manager->addPermission('posts.edit');
    $manager->addPermission('posts.delete');
    $manager->permit($user, 'posts.edit');
    $manager->permit($user, 'posts.delete');
    
    expect($manager->can($user, 'posts.edit'))->toBeTrue();
    expect($manager->can($user, 'posts.delete'))->toBeTrue();
    
    // Deny all in 'global' namespace (null namespace)
    $manager->deny($user, '*');
    
    expect($manager->can($user, 'posts.edit'))->toBeFalse();
    expect($manager->can($user, 'posts.delete'))->toBeFalse();
});

it('denies access via specific namespace wildcard (blog:*)', function () {
    $manager = resolve(AccessManager::class);
    $user = new User(1);
    
    $manager->addPermission('edit', namespace: 'blog');
    $manager->permit($user, 'blog:edit');
    
    expect($manager->can($user, 'blog:edit'))->toBeTrue();
    
    $manager->deny($user, 'blog:*');
    
    expect($manager->can($user, 'blog:edit'))->toBeFalse();
});

it('denies access via role denial', function () {
    $manager = resolve(AccessManager::class);
    $user = new User(1);
    
    $manager->addRole('editor', permissions: ['posts.edit']);
    $manager->assignRole($user, 'editor');
    
    expect($manager->can($user, 'posts.edit'))->toBeTrue();
    
    $manager->denyRole($user, 'editor');
    
    expect($manager->can($user, 'posts.edit'))->toBeFalse();
    expect($manager->hasRole($user, 'editor'))->toBeFalse();
});

it('respects temporal denials (expiring)', function () {
    $manager = resolve(AccessManager::class);
    $user = new User(1);
    
    $manager->addPermission('posts.edit');
    $manager->permit($user, 'posts.edit');
    
    // Deny for 1 second (past)
    $past = new \DateTime('-1 minute');
    $manager->deny($user, 'posts.edit', expiresAt: $past);
    
    expect($manager->can($user, 'posts.edit'))->toBeTrue();
    
    // Deny for future
    $future = new \DateTime('+1 minute');
    $manager->deny($user, 'posts.edit', expiresAt: $future);
    
    expect($manager->can($user, 'posts.edit'))->toBeFalse();
});

it('denies everything via account suspension (*:*)', function () {
    $manager = resolve(AccessManager::class);
    $user = new User(1);
    
    $manager->addPermission('posts.edit');
    $manager->permit($user, 'posts.edit');
    $manager->addRole('admin');
    $manager->assignRole($user, 'admin');
    
    expect($manager->can($user, 'posts.edit'))->toBeTrue();
    
    // Global suspension
    $manager->deny($user, '*');
    
    expect($manager->can($user, 'posts.edit'))->toBeFalse();
    expect($manager->isSuperAdmin($user))->toBeFalse(); // Even if they were superadmin
});

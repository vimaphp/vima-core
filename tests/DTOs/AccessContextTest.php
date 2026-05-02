<?php

use Vima\Core\DTOs\AccessContext;
use Vima\Core\Services\AccessManager;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
use function Vima\Core\resolve;

beforeEach(function () {
    initDependencies();
});

it('checks roles correctly', function () {
    $manager = resolve(AccessManager::class);
    $user = new UserMock(1);
    
    $role1 = $manager->addRole('admin');
    $role2 = $manager->addRole('editor');
    $manager->assignRole($user, 'admin');

    $ctx = new AccessContext($user, 'view', $manager);

    expect($ctx->is('admin'))->toBeTrue();
    expect($ctx->is('editor'))->toBeFalse();
    expect($ctx->isAny(['admin', 'viewer']))->toBeTrue();
    expect($ctx->isAny(['editor', 'viewer']))->toBeFalse();
    expect($ctx->isAll(['admin']))->toBeTrue();
    expect($ctx->isAll(['admin', 'editor']))->toBeFalse();
});

it('checks super admin status', function () {
    $manager = resolve(AccessManager::class);
    $user = new UserMock(1);
    
    $manager->addRole('super-admin');
    $manager->assignRole($user, 'super-admin');
    
    // Configure super admin role
    $manager->getConfig()->superAdminRole = 'super-admin';

    $ctx = new AccessContext($user, 'edit', $manager);
    expect($ctx->isSuperAdmin())->toBeTrue();
});

it('checks ownership correctly', function () {
    $manager = resolve(AccessManager::class);
    $user = new UserMock(123);
    $ctx = new AccessContext($user, 'edit', $manager);

    // Array resource
    expect($ctx->owns(['user_id' => 123]))->toBeTrue();
    expect($ctx->owns(['user_id' => 456]))->toBeFalse();
    expect($ctx->owns(['owner' => 123], 'owner'))->toBeTrue();

    // Object resource
    $resource = new stdClass();
    $resource->user_id = 123;
    expect($ctx->owns($resource))->toBeTrue();
    
    $resource->user_id = 456;
    expect($ctx->owns($resource))->toBeFalse();

    // Invalid resource
    expect($ctx->owns('not-a-resource'))->toBeFalse();
});

it('delegates can() to manager', function () {
    $manager = resolve(AccessManager::class);
    $user = new UserMock(1);
    $manager->addPermission('posts.edit', namespace: 'blog');
    $manager->permit($user, 'blog:posts.edit');

    $ctx = new AccessContext($user, 'some.action', $manager);
    
    expect($ctx->can('blog:posts.edit'))->toBeTrue();
    expect($ctx->can('other:action'))->toBeFalse();
});

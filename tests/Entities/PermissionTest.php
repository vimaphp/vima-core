<?php

use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Entities\Permission;
use function Vima\Core\resolve;

it('creates a permission with resource.action format', function () {
    $permission = new Permission(name: 'posts.create', description: 'user can create a post');

    expect($permission->name)->toBe('posts.create');
    expect($permission->description)->toBe('user can create a post');
});

it('can save itself via the AccessManager', function () {
    initDependencies();

    $permissionRepo = resolve(PermissionRepositoryInterface::class);

    $permission = new Permission('active-record-perm');

    $savedPerm = $permission->save();
    expect($savedPerm->id)->not->toBeNull();
    expect($permissionRepo->findByName('active-record-perm'))->not->toBeNull();
});

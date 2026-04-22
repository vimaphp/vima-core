<?php

use Vima\Core\Entities\Permission;
use Vima\Core\Exceptions\PermissionNotFoundException;
use Vima\Core\Services\PermissionManager;
use function Vima\Core\resolve;

beforeEach(function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    initDependencies();

    $this->permissionManager = resolve(PermissionManager::class);
});

it('creates a permission', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $perm = $this->permissionManager->create('posts.delete');

    expect($perm)->toBeInstanceOf(Permission::class)
        ->and($perm->name)->toBe('posts.delete');
});

it('compares permissions equality by name', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $a = $this->permissionManager->create('posts.edit');
    $b = $this->permissionManager->create('posts.edit');

    expect($a->name)->toBe($b->name);
});

it('throws exception if permission not found', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    expect($this->permissionManager->find("posts.create"))->toBeNull();
});

<?php

use Vima\Core\Entities\Permission;
use Vima\Core\Exceptions\PermissionNotFoundException;
use Vima\Core\Services\PermissionManager;
use Vima\Core\Storage\InMemory\InMemoryPermissionRepository;

beforeEach(function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $this->permissionRepo = new InMemoryPermissionRepository();
    $this->permissionManager = new PermissionManager($this->permissionRepo);
});

it('creates a permission', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $perm = $this->permissionManager->create('posts.delete');

    expect($perm)->toBeInstanceOf(Permission::class)
        ->and($perm->getName())->toBe('posts.delete');
});

it('compares permissions equality by name', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $a = $this->permissionManager->create('posts.edit');
    $b = $this->permissionManager->create('posts.edit');

    expect($a->getName())->toBe($b->getName());
});

it('throws exception if permission not found', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    expect($this->permissionManager->find("posts.create"));
})->throws(PermissionNotFoundException::class);

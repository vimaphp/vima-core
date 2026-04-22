<?php

use Vima\Core\Contracts\EventDispatcherInterface;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Exceptions\RoleNotFoundException;
use Vima\Core\Services\RoleManager;
use Vima\Core\Events\Repository\RepositoryAction;
use function Vima\Core\{resolve};

beforeEach(function () {
    initDependencies();
    /** @var RoleManager */
    $this->roleManager = resolve(RoleManager::class);

    /** @var EventDispatcherInterface */
    $this->dispatcher = resolve(EventDispatcherInterface::class);
});

it('creates a role with permissions', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    /* Second way to create a role with permissions */
    $role = Role::define(
        name: 'editor',
        permissions: [
            Permission::define(name: 'posts.create'),
            Permission::define(name: 'posts.edit')
        ]
    );

    $this->roleManager->create($role);

    $role = $this->roleManager->find("editor");

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->isPermitted('posts.create'))->toBeTrue()
        ->and($role->isPermitted('posts.edit'))->toBeTrue();
});

it('finds role by name', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $newRole = Role::define(
        name: 'admin',
        permissions: [
            Permission::define('users.delete'),
        ]
    );


    $role = $this->roleManager->create($newRole);

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->isPermitted('users.delete'))->toBeTrue();
});

it('throws exception if role not found', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    expect($this->roleManager->find('ghost'))->toBeNull();
});

it('supports role inheritance', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $admin = new Role(name: 'admin', permissions: [new Permission('admin.access')]);
    $editor = new Role(name: 'editor', permissions: [new Permission('posts.edit')]);
    $editor->inherit($admin)->save();

    $this->roleManager->save($admin);
    $this->roleManager->save($editor);

    $role = $this->roleManager->find('editor', resolve: true);

    expect($role->parents)->toHaveCount(1)
        ->and($role->parents[0]->name)->toBe('admin')
        ->and($role->isPermitted('posts.edit'))->toBeTrue()
        ->and($role->isPermitted('admin.access'))->toBeTrue();
});

it('dispatches events', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $this->dispatcher->dispatched = []; // Clear events from beforeEach
    $this->roleManager->create('new-role');

    expect($this->dispatcher->dispatched)->toHaveCount(1)
        ->and($this->dispatcher->dispatched[0])->toBeInstanceOf(RepositoryAction::class)
        ->and($this->dispatcher->dispatched[0]->action)->toBe(RepositoryAction::ACTION_CREATED);
});

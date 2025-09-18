<?php

use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Exceptions\RoleNotFoundException;
use Vima\Core\Services\RoleManager;
use Vima\Core\Storage\InMemory\InMemoryRoleRepository;

beforeEach(function () {
    /** @var \Tests\ManagerTestCase $this */

    $this->roleRepo = new InMemoryRoleRepository();

    // create a bunch of users
    foreach ([new Role('admin'), new Role('user')] as $user) {
        $this->roleRepo->save($user);
    };

    $this->roleManager = new RoleManager($this->roleRepo);
});

it('creates a role with permissions', function () {
    /** @var \Tests\ManagerTestCase $this   */

    /* One way to create a role with permissions */
    /* $role = $this->roleManager->create('editor');

    foreach ([new Permission('posts.create'), new Permission('posts.edit')] as $p) {
        $role->addPermission($p);
    }

    $this->roleManager->save($role);
 */
    /* Second way to create a role with permissions */
    $role = new Role('editor', [new Permission('posts.create'), new Permission('posts.edit')]);

    $this->roleManager->create($role);

    $role = $this->roleManager->find("editor");

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->hasPermission('posts.create'))->toBeTrue()
        ->and($role->hasPermission('posts.edit'))->toBeTrue();
});

it('finds role by name', function () {
    /** @var \Tests\ManagerTestCase $this */

    $this->roleManager->create('admin');
    $role = $this->roleManager->find('admin');
    $permission = new Permission('users.delete');
    $role->addPermission($permission);

    $this->roleManager->save($role);

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->hasPermission('users.delete'))->toBeTrue();
});

it('throws exception if role not found', function () {
    /** @var \Tests\ManagerTestCase $this */

    expect($this->roleManager->find('ghost'));
})->throws(RoleNotFoundException::class);

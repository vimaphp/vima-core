<?php

use Vima\Core\Config\VimaConfig;
use Vima\Core\DependencyContainer;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Exceptions\RoleNotFoundException;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Services\RoleManager;
use Vima\Core\Services\UserResolver;
use Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository;

beforeEach(function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $this->roleRepo = new InMemoryRoleRepository();

    // create a bunch of users
    foreach ([new Role('admin'), new Role('user')] as $user) {
        $this->roleRepo->save($user);
    };

    new DependencyContainer(
        roles: new InMemoryRoleRepository(),
        permissions: new InMemoryPermissionRepository(),
        userPermissions: new InMemoryUserPermissionRepository(),
        userRoles: new InMemoryUserRoleRepository(),
        rolePermissions: new InMemoryRolePermissionRepository(),
        userResolver: new UserResolver(new VimaConfig()),
        policies: new PolicyRegistry(),
    );

    $this->roleManager = new RoleManager();
});

it('creates a role with permissions', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this   */

    /* One way to create a role with permissions */
    /* $role = $this->roleManager->create('editor');

    foreach ([new Permission('posts.create'), new Permission('posts.edit')] as $p) {
        $role->addPermission($p);
    }

    $this->roleManager->save($role);
 */
    /* Second way to create a role with permissions */
    $role = new Role(name: 'editor', permissions: [new Permission(name: 'posts.create'), new Permission(name: 'posts.edit')]);

    $this->roleManager->create($role);

    $role = $this->roleManager->find("editor");

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->hasPermission('posts.create'))->toBeTrue()
        ->and($role->hasPermission('posts.edit'))->toBeTrue();
});

it('finds role by name', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $this->roleManager->create('admin');
    $role = $this->roleManager->find('admin');
    $permission = new Permission('users.delete');
    $role->addPermission($permission);

    $this->roleManager->save($role);

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->hasPermission('users.delete'))->toBeTrue();
});

it('throws exception if role not found', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    expect($this->roleManager->find('ghost'));
})->throws(RoleNotFoundException::class);

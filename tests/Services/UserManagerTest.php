<?php

use Vima\Core\Entities\{User, Role, Permission};
use Vima\Core\Exceptions\UserNotFoundException;
use Vima\Core\Services\UserManager;
use Vima\Core\Storage\InMemory\InMemoryUserRepository;

beforeEach(function () {
    /** @var \Tests\ManagerTestCase $this */

    $this->userRepo = new InMemoryUserRepository();

    // create a bunch of users
    foreach ([new User(101), new User(202)] as $user) {
        $this->userRepo->save($user);
    };

    $this->userManager = new UserManager($this->userRepo);
});

it('creates a user with id', function () {
    /** @var \Tests\ManagerTestCase $this */

    $user = $this->userManager->find(101);
    expect($user)->toBeInstanceOf(User::class)
        ->and($user->getId())->toBe(101);
});

it('assigns roles to user', function () {
    /** @var \Tests\ManagerTestCase $this */
    /** @var UserManager $this->userManager */

    /** @var User */
    $user = $this->userManager->find(202);

    $role = new Role('editor');
    $role->addPermission(new Permission('posts.create'));

    $user->addRole($role);

    $this->userManager->save($user);

    /** @var User */
    $user = $this->userManager->find(202);

    expect($user->hasPermission('posts.create'))->toBeTrue();
});


it('throws exception if user not found', function () {
    /** @var \Tests\ManagerTestCase $this */

    expect($this->userManager->find(20));
})->throws(UserNotFoundException::class);
<?php

use Vima\Core\Entities\UserDeny;
use Vima\Core\Entities\Permission;
use Vima\Core\Entities\Bare\BarePermission;
use Vima\Core\Contracts\UserDenyRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\UserRepositoryInterface;
use Vima\Core\Contracts\UserInterface;
use function Vima\Core\resolve;

beforeEach(function () {
    initDependencies();
});

it('can be instantiated and defined', function () {
    $deny = new UserDeny(user_id: 1, permission_id: 10, reason: 'Too many attempts');
    
    expect($deny->user_id)->toBe(1);
    expect($deny->permission_id)->toBe(10);
    expect($deny->reason)->toBe('Too many attempts');

    $deny = UserDeny::define(2, 20, 'Manual override');
    expect($deny->user_id)->toBe(2);
    expect($deny->permission_id)->toBe(20);
    expect($deny->reason)->toBe('Manual override');
});

it('can save and delete itself', function () {
    $denyRepo = resolve(UserDenyRepositoryInterface::class);
    
    $deny = UserDeny::define(1, 10, 'Test reason');
    $savedDeny = $deny->save();

    expect($savedDeny)->toBeInstanceOf(UserDeny::class);
    // InMemoryUserDenyRepository should have it now
    expect($denyRepo->isDenied(1, 10))->toBeTrue();

    $deny->delete();
    expect($denyRepo->isDenied(1, 10))->toBeFalse();
});

it('can retrieve permission and user objects', function () {
    $permRepo = resolve(PermissionRepositoryInterface::class);
    $userRepo = resolve(UserRepositoryInterface::class);

    $perm = new BarePermission(id: 55, name: 'posts.delete');
    $permRepo->save($perm);

    // Mock a user object that implements UserInterface
    $userMock = new class implements UserInterface {
        public function vimaGetId(): string|int { return 123; }
        public function vimaGetRoles(): array { return []; }
    };
    $userRepo->save($userMock);

    $deny = UserDeny::define(123, 55);
    
    expect($deny->getPermission()->name)->toBe('posts.delete');
    expect($deny->getUser()->vimaGetId())->toBe(123);
});

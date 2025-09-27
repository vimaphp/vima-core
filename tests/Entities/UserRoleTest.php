<?php

use Vima\Core\Entities\UserRole;

it('can be instantiated with constructor', function () {
    $role = new UserRole(id: 1, user_id: 42, role_id: 99);

    expect($role->id)->toBe(1)
        ->and($role->user_id)->toBe(42)
        ->and($role->role_id)->toBe(99);
});

it('can be instantiated without id', function () {
    $role = new UserRole(user_id: 100, role_id: 200);

    expect($role->id)->toBeNull()
        ->and($role->user_id)->toBe(100)
        ->and($role->role_id)->toBe(200);
});

it('can be created using the define static method', function () {
    $role = UserRole::define(user_id: 7, role_id: 3);

    expect($role)->toBeInstanceOf(UserRole::class)
        ->and($role->id)->toBeNull()
        ->and($role->user_id)->toBe(7)
        ->and($role->role_id)->toBe(3);
});

it('supports string ids as well as integers', function () {
    $role = new UserRole(id: 'abc123', user_id: 'userX', role_id: 'roleY');

    expect($role->id)->toBe('abc123')
        ->and($role->user_id)->toBe('userX')
        ->and($role->role_id)->toBe('roleY');
});

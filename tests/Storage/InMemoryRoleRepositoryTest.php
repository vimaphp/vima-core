<?php

use Vima\Core\Entities\Role;
use Vima\Core\Storage\InMemory\InMemoryRoleRepository;

it('saves and finds a role by name', function () {
    $repo = new InMemoryRoleRepository();
    $role = new Role('admin');
    $repo->save($role);

    $found = $repo->findByName('admin');

    expect($found)->toBeInstanceOf(Role::class)
        ->and($found->getName())->toBe('admin');
});

it('returns null for missing role', function () {
    $repo = new InMemoryRoleRepository();
    $found = $repo->findByName('ghost');

    expect($found)->toBeNull();
});

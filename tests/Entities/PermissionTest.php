<?php

use Vima\Core\Entities\Permission;

it('creates a permission with resource.action format', function () {
    $permission = new Permission(name: 'posts.create', description: 'user can create a post');

    expect($permission->name)->toBe('posts.create');
    expect($permission->description)->toBe('user can create a post');
});

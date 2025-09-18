<?php

use Vima\Core\Entities\Permission;

it('creates a permission with resource.action format', function () {
    $permission = new Permission('posts.create', 'user can create a post');

    expect($permission->getName())->toBe('posts.create');
    expect($permission->getDescription())->toBe('user can create a post');
});

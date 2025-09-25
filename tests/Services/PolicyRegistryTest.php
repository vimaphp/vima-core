<?php

use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Tests\Fixtures\User;

it('registers and evaluates a policy successfully', function () {
    $registry = new PolicyRegistry();
    $registry->register('posts.update', fn(User $u, $post) => $u->vimaGetId() === $post->ownerId);

    $user = new User(1);
    $post = (object) ['ownerId' => 1];

    expect($registry->evaluate($user, 'posts.update', $post))->toBeTrue();
});

it('returns false if user does not satisfy policy', function () {
    $registry = new PolicyRegistry();
    $registry->register('posts.update', fn(User $u, $post) => $u->vimaGetId() === $post->ownerId);

    $user = new User(2);
    $post = (object) ['ownerId' => 1];

    expect($registry->evaluate($user, 'posts.update', $post))->toBeFalse();
});

it('returns false if policy is not found', function () {
    $registry = new PolicyRegistry();

    $user = new User(1);
    $post = (object) ['ownerId' => 1];

    expect($registry->evaluate($user, 'posts.update', $post))->toBeFalse();
});

it('can define policies statically with one rule', function () {
    $registry = PolicyRegistry::define([
        'posts.update' => fn(User $u, $post) => $u->vimaGetId() === $post->ownerId,
    ]);

    $user = new User(1);
    $post = (object) ['ownerId' => 1];

    expect($registry->evaluate($user, 'posts.update', $post))->toBeTrue();
});

it('can define multiple policies statically', function () {
    $registry = PolicyRegistry::define([
        'posts.update' => fn(User $u, $post) => $u->vimaGetId() === $post->ownerId,
        'posts.delete' => fn(User $u, $post) => $post->ownerId === 1, // simple check
    ]);

    $user = new User(1);
    $post = (object) ['ownerId' => 1];

    expect($registry->evaluate($user, 'posts.update', $post))->toBeTrue()
        ->and($registry->evaluate($user, 'posts.delete', $post))->toBeTrue();
});

it('returns false for undefined policy in static define', function () {
    $registry = PolicyRegistry::define([]);

    $user = new User(1);

    expect($registry->evaluate($user, 'posts.publish', new stdClass()))->toBeFalse();
});

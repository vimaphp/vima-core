<?php

use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Tests\Fixtures\User;
use Vima\Core\Contracts\PolicyInterface;

class TestPost
{
    public int $ownerId;
    public function __construct(int $ownerId)
    {
        $this->ownerId = $ownerId;
    }
}

class TestPostPolicy implements PolicyInterface
{
    public static function getResource(): string
    {
        return TestPost::class;
    }
    public function canUpdate(User $user, TestPost $post): bool
    {
        return $user->vimaGetId() === $post->ownerId;
    }
    public function canEdit(User $user, TestPost $post): bool
    {
        return true;
    }
}

class InvalidPolicy
{
    public function canUpdate()
    {
        return true;
    }
}

it('registers and evaluates a callback policy successfully', function () {
    $registry = new PolicyRegistry();
    $registry->register('posts.update', fn(User $u, $post) => $u->vimaGetId() === $post['ownerId']);

    $user = new User(1);
    $post = ['ownerId' => 1];

    expect($registry->evaluate($user, 'posts.update', $post))->toBeTrue();
});

it('registers and evaluates a class-based policy successfully', function () {
    $registry = new PolicyRegistry();
    $registry->registerClass(TestPost::class, TestPostPolicy::class);

    $user = new User(1);
    $post = new TestPost(1);

    expect($registry->evaluate($user, 'posts.update', $post))->toBeTrue();
    expect($registry->evaluate($user, 'update', $post))->toBeTrue();
    expect($registry->evaluate($user, 'posts.edit', $post))->toBeTrue();
});

it('enforces PolicyInterface during registration', function () {
    $registry = new PolicyRegistry();
    $registry->registerClass(TestPost::class, InvalidPolicy::class);
})->throws(\InvalidArgumentException::class, "Policy class InvalidPolicy must implement Vima\Core\Contracts\PolicyInterface");

it('throws exception if resource provided but no policy registered', function () {
    $registry = new PolicyRegistry();
    $user = new User(1);
    $post = new TestPost(1);

    $registry->evaluate($user, 'posts.update', $post);
})->throws(\Exception::class, "No policy class registered for resource: TestPost");

it('returns false if user does not satisfy policy', function () {
    $registry = new PolicyRegistry();
    $registry->register('posts.update', fn(User $u, $post) => $u->vimaGetId() === $post['ownerId']);

    $user = new User(2);
    $post = ['ownerId' => 1];

    expect($registry->evaluate($user, 'posts.update', $post))->toBeFalse();
});

it('returns false if policy is not found (no resource provided)', function () {
    $registry = new PolicyRegistry();

    $user = new User(1);
    expect($registry->evaluate($user, 'posts.update'))->toBeFalse();
});

it('can define policies statically with one rule', function () {
    $registry = PolicyRegistry::define([
        'posts.update' => fn(User $u, $post) => $u->vimaGetId() === $post['ownerId'],
    ]);

    $user = new User(1);
    $post = ['ownerId' => 1];

    expect($registry->evaluate($user, 'posts.update', $post))->toBeTrue();
});

it('passes multiple arguments correctly to policy methods', function () {
    $registry = new PolicyRegistry();

    class MultiArgPolicy implements PolicyInterface
    {
        public static function getResource(): string
        {
            return TestPost::class;
        }
        public function canApprove(User $user, TestPost $post, bool $isSuper): bool
        {
            return $isSuper;
        }
    }

    $registry->registerClass(TestPost::class, MultiArgPolicy::class);
    $user = new User(1);
    $post = new TestPost(1);

    expect($registry->evaluate($user, 'approve', $post, true))->toBeTrue();
    expect($registry->evaluate($user, 'approve', $post, false))->toBeFalse();
});

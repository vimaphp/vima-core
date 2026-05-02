<?php

use Vima\Core\Attributes\MapToPermission;
use Vima\Core\DTOs\AccessContext;
use Vima\Core\Exceptions\PolicyNotFoundException;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Tests\Fixtures\User;
use Vima\Core\Contracts\PolicyInterface;

beforeEach(function () {
    initDependencies();
});

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
    public function canUpdate(AccessContext $ctx, TestPost $post): bool
    {
        return $ctx->owns($post, 'ownerId');
    }
    public function canEdit(AccessContext $ctx, TestPost $post): bool
    {
        return true;
    }

    #[MapToPermission('delete')]
    public function someDeleteMethod(AccessContext $ctx, TestPost $post): bool
    {
        return $post->ownerId === 1;
    }

    #[MapToPermission('publish', namespace: 'blog')]
    public function namespacedPublish(AccessContext $ctx, TestPost $post): bool
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
    $registry->register('posts.update', fn(AccessContext $ctx, $post) => $ctx->owns($post, 'ownerId'));

    $user = new User(1);
    $post = ['ownerId' => 1];

    expect($registry->evaluate($user, 'posts.update', null, $post))->toBeTrue();
});

it('registers and evaluates a class-based policy successfully', function () {
    $registry = new PolicyRegistry();
    $registry->registerClass(TestPost::class, TestPostPolicy::class);

    $user = new User(1);
    $post = new TestPost(1);

    expect($registry->evaluate($user, 'posts.update', null, $post))->toBeTrue();
    expect($registry->evaluate($user, 'update', null, $post))->toBeTrue();
    expect($registry->evaluate($user, 'posts.edit', null, $post))->toBeTrue();
});

it('enforces PolicyInterface during registration', function () {
    $registry = new PolicyRegistry();
    $registry->registerClass(TestPost::class, InvalidPolicy::class);
})->throws(\InvalidArgumentException::class, "Policy class InvalidPolicy must implement Vima\Core\Contracts\PolicyInterface");

it('throws exception if resource provided but no policy registered', function () {
    $registry = new PolicyRegistry();
    $user = new User(1);
    $post = new TestPost(1);

    $registry->evaluate($user, 'posts.update', null, $post);
})->throws(\Exception::class, "No policy registered for ability/resource: posts.update");

it('returns false if user does not satisfy policy', function () {
    $registry = new PolicyRegistry();
    $registry->register('posts.update', fn(AccessContext $ctx, $post) => $ctx->owns($post, 'ownerId'));

    $user = new User(2);
    $post = ['ownerId' => 1];

    expect($registry->evaluate($user, 'posts.update', null, $post))->toBeFalse();
});

it('throws an exception if policy is not found (no resource provided)', function () {
    $registry = new PolicyRegistry();

    $user = new User(1);
    expect($registry->evaluate($user, 'posts.update'))->toBeFalse();
})->throws(PolicyNotFoundException::class, 'No policy registered for ability/resource: posts.update');

it('can define policies statically with one rule', function () {
    $registry = PolicyRegistry::define([
        'posts.update' => fn(AccessContext $ctx, $post) => $ctx->owns($post, 'ownerId'),
    ]);

    $user = new User(1);
    $post = ['ownerId' => 1];

    expect($registry->evaluate($user, 'posts.update', null, $post))->toBeTrue();
});

it('passes multiple arguments correctly to policy methods', function () {
    $registry = new PolicyRegistry();

    class MultiArgPolicy implements PolicyInterface
    {
        public static function getResource(): string
        {
            return TestPost::class;
        }
        public function canApprove(AccessContext $ctx, TestPost $post): bool
        {
            return $ctx->additionalContext[0] === true;
        }
    }

    $registry->registerClass(TestPost::class, MultiArgPolicy::class);
    $user = new User(1);
    $post = new TestPost(1);

    expect($registry->evaluate($user, 'approve', null, $post, true))->toBeTrue();
    expect($registry->evaluate($user, 'approve', null, $post, false))->toBeFalse();
});

it('maps methods using MapToPermission attribute successfully', function () {
    $registry = new PolicyRegistry();
    $registry->registerClass(TestPost::class, TestPostPolicy::class);

    $user = new User(1);
    $post = new TestPost(1);

    expect($registry->evaluate($user, 'delete', null, $post))->toBeTrue();
    
    $post2 = new TestPost(2);
    expect($registry->evaluate($user, 'delete', null, $post2))->toBeFalse();
});

it('respects namespace in MapToPermission attribute', function () {
    $registry = new PolicyRegistry();
    $registry->registerClass(TestPost::class, TestPostPolicy::class);

    $user = new User(1);
    $post = new TestPost(1);

    // blog:publish should match namespacedPublish
    expect($registry->evaluate($user, 'blog:publish', null, $post))->toBeTrue();
    
    // publish without namespace should fail (or use default naming if exists)
    // currently 'publish' -> 'canPublish' which doesn't exist
    expect(fn() => $registry->evaluate($user, 'publish', null, $post))
        ->toThrow(\Vima\Core\Exceptions\PolicyMethodNotFoundException::class);
});

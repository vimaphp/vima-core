<?php

use Vima\Core\Services\UserResolver;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Config\UserMethods;
use Vima\Core\Exceptions\UserResolutionException;

it('resolves ID via custom resolver', function () {
    $config = new VimaConfig(userResolver: fn($u) => $u['id']);
    $resolver = new UserResolver($config);
    
    expect($resolver->resolveId(['id' => 123]))->toBe(123);
});

it('throws exception for array without custom resolver', function () {
    $resolver = new UserResolver();
    expect(fn() => $resolver->resolveId(['id' => 123]))
        ->toThrow(UserResolutionException::class, 'Use the Vima::userResolver property to provide a resolver for the user');
});

it('resolves ID via vimaGetId method', function () {
    $user = new class {
        public function vimaGetId() { return 'user-456'; }
    };
    $resolver = new UserResolver();
    expect($resolver->resolveId($user))->toBe('user-456');
});

it('resolves ID via mapped method', function () {
    $user = new class {
        public function getUid() { return 789; }
    };
    $config = new VimaConfig(userMethods: new UserMethods(id: 'getUid'));
    $resolver = new UserResolver($config);
    expect($resolver->resolveId($user))->toBe(789);
});

it('resolves ID via toVimaIdentity', function () {
    $user = new class {
        public function toVimaIdentity() { return (object)['id' => 'ident-1']; }
    };
    $resolver = new UserResolver();
    expect($resolver->resolveId($user))->toBe('ident-1');
});

it('throws exception if ID cannot be resolved', function () {
    $user = new stdClass();
    $resolver = new UserResolver();
    expect(fn() => $resolver->resolveId($user))
        ->toThrow(UserResolutionException::class, 'Could not resolve roles for user. Check the documentation on user resolution.');
});

it('returns custom resolver closure if set', function () {
    $resolverFunc = fn($u) => 1;
    $config = new VimaConfig(userResolver: $resolverFunc);
    $resolver = new UserResolver($config);
    
    expect($resolver->getIdResolver())->toBe($resolverFunc);
});

it('returns fallback closure if no custom resolver set', function () {
    $resolver = new UserResolver();
    $closure = $resolver->getIdResolver();
    
    expect($closure)->toBeInstanceOf(Closure::class);
    
    $user = new class { public function vimaGetId() { return 99; } };
    expect($closure($user))->toBe(99);
});

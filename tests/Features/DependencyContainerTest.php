<?php

use Vima\Core\DependencyContainer;
use function Vima\Core\{register, registerMany, resolve, container};

beforeEach(function () {
    DependencyContainer::reset();
});

it('can register and resolve a simple class', function () {
    class ExampleServiceA
    {
        public function __destruct()
        {
        }
    }

    register(ExampleServiceA::class);

    $service = resolve(ExampleServiceA::class);

    expect($service)->toBeInstanceOf(ExampleServiceA::class);
});

it('can register an instance explicitly', function () {
    class ExampleServiceB
    {
    }

    $instance = new ExampleServiceB();

    register(ExampleServiceB::class, $instance);

    $service = resolve(ExampleServiceB::class);

    expect($service)->toBeInstanceOf(ExampleServiceB::class);
});

it('throws an exception if dependency is not found', function () {
    DependencyContainer::reset();

    expect(fn() => resolve('NonExistentClass'))
        ->toThrow(RuntimeException::class);
});

it('can register many dependencies at once', function () {
    class ServiceX
    {
    }
    class ServiceY
    {
    }

    registerMany([
        ServiceX::class,
        ServiceY::class,
    ]);

    expect(resolve(ServiceX::class))->toBeInstanceOf(ServiceX::class)
        ->and(resolve(ServiceY::class))->toBeInstanceOf(ServiceY::class);
});

it('can register many with associative bindings', function () {
    class ServiceZ
    {
    }
    class ServiceW
    {
    }

    $instanceZ = new ServiceZ();

    registerMany([
        ServiceZ::class => $instanceZ,
        ServiceW::class,
    ]);

    expect(resolve(ServiceZ::class))->toBeInstanceOf(ServiceZ::class)
        ->and(resolve(ServiceW::class))->toBeInstanceOf(ServiceW::class);
});

it('returns the same singleton instance when resolved multiple times', function () {
    class SingletonExample
    {
    }

    register(SingletonExample::class);

    $s1 = resolve(SingletonExample::class);
    $s2 = resolve(SingletonExample::class);

    expect($s1)->toBe($s2);
});

it('exposes the container helper', function () {
    class ExampleServiceC
    {
    }

    register(ExampleServiceC::class);

    $c = container();

    expect($c)->toBeInstanceOf(DependencyContainer::class)
        ->and($c->get(ExampleServiceC::class))->toBeInstanceOf(ExampleServiceC::class);
});

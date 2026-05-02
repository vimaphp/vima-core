<?php

use function Vima\Core\container;
use function Vima\Core\resolve;
use function Vima\Core\make;
use function Vima\Core\register;
use function Vima\Core\registerMany;
use function Vima\Core\singleton;
use Vima\Core\DependencyContainer;

beforeEach(function () {
    DependencyContainer::reset();
});

it('resolves using make alias', function () {
    register('foo', (object)['bar' => 'baz']);
    expect(make('foo')->bar)->toBe('baz');
});

it('registers singletons', function () {
    $instance = (object)['foo' => 'bar'];
    singleton('single', $instance);
    
    expect(resolve('single'))->toBe($instance);
});

it('returns container instance', function () {
    expect(container())->toBeInstanceOf(DependencyContainer::class);
});

<?php

use Vima\Core\Exceptions\PermissionNotFoundException;
use Vima\Core\Exceptions\RoleNotFoundException;
use Vima\Core\Exceptions\PolicyMethodNotFoundException;
use Vima\Core\Events\Event;
use Vima\Core\Events\DefaultEventDispatcher;
use Vima\Core\Services\NullCache;
use Vima\Core\Events\Grant\PermissionRevoked;
use Vima\Core\Entities\Permission;

it('instantiates exceptions', function () {
    expect(new PermissionNotFoundException('test'))->toBeInstanceOf(\Exception::class);
    expect(new RoleNotFoundException('test'))->toBeInstanceOf(\Exception::class);
    expect(new PolicyMethodNotFoundException('test', 'method'))->toBeInstanceOf(\Exception::class);
});

it('tests base Event class', function () {
    $event = new class(['foo' => 'bar']) extends Event {
        public const NAME = 'test.event';
    };
    
    expect($event->getData())->toBe(['foo' => 'bar']);
    expect($event->get('foo'))->toBe('bar');
    expect($event->get('missing', 'default'))->toBe('default');
    expect($event->getName())->toBe('test.event');
    
    $event->set('baz', 123);
    expect($event->get('baz'))->toBe(123);
});

it('tests DefaultEventDispatcher', function () {
    $dispatcher = new DefaultEventDispatcher();
    $event = new stdClass();
    
    $dispatcher->dispatch($event);
    expect($dispatcher->getDispatchedEvents())->toHaveCount(1);
    expect($dispatcher->getDispatchedEvents()[0])->toBe($event);
    
    $dispatcher->clear();
    expect($dispatcher->getDispatchedEvents())->toHaveCount(0);
});

it('tests NullCache', function () {
    $cache = new NullCache();
    
    expect($cache->get('any', 'default'))->toBe('default');
    expect($cache->set('any', 'value'))->toBeTrue();
    expect($cache->delete('any'))->toBeTrue();
    expect($cache->clear())->toBeTrue();
});

it('tests specific event classes', function () {
    $perm = new Permission('test');
    $event = new PermissionRevoked(1, $perm);
    
    expect($event->get('user_id'))->toBe(1);
    expect($event->get('permission'))->toBe($perm);
});

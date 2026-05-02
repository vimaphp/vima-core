<?php

use Vima\Core\Services\SymfonyCacheAdapter;

it('stores and retrieves items', function () {
    $cache = new SymfonyCacheAdapter();
    $cache->clear();

    $cache->set('test_key', 'test_value');
    expect($cache->get('test_key'))->toBe('test_value');
});

it('returns default if key not found', function () {
    $cache = new SymfonyCacheAdapter();
    expect($cache->get('non_existent', 'default'))->toBe('non_existent' === 'non_existent' ? 'default' : null);
});

it('deletes items', function () {
    $cache = new SymfonyCacheAdapter();
    $cache->set('delete_me', 123);
    expect($cache->get('delete_me'))->toBe(123);
    
    $cache->delete('delete_me');
    expect($cache->get('delete_me'))->toBeNull();
});

it('clears all items', function () {
    $cache = new SymfonyCacheAdapter();
    $cache->set('a', 1);
    $cache->set('b', 2);
    
    $cache->clear();
    
    expect($cache->get('a'))->toBeNull();
    expect($cache->get('b'))->toBeNull();
});

it('sanitizes keys', function () {
    $cache = new SymfonyCacheAdapter();
    // Keys with reserved characters like : should be handled by sanitizeKey internally
    $key = 'vima:role:1:perms';
    $cache->set($key, ['edit']);
    expect($cache->get($key))->toBe(['edit']);
});

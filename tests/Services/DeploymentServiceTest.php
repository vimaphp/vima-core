<?php

use Vima\Core\Services\DeploymentService;
use Vima\Core\Services\AccessManager;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Contracts\CacheInterface;
use function Vima\Core\resolve;
use function Vima\Core\register;

beforeEach(function () {
    initDependencies();
});

it('optimizes roles and policies', function () {
    // Explicitly register SymfonyCacheAdapter for this test to ensure persistence
    register(\Vima\Core\Contracts\CacheInterface::class, new \Vima\Core\Services\SymfonyCacheAdapter());
    
    $manager = resolve(AccessManager::class);
    $registry = resolve(PolicyRegistry::class);
    $cache = resolve(CacheInterface::class);
    $cache->clear();

    // Create a role to optimize
    $role = $manager->addRole('test_role', permissions: ['edit']);
    $manager->getConfig()->cacheEnabled = true;
    
    $deployment = new DeploymentService(
        resolve(\Vima\Core\Services\RoleManager::class),
        $registry,
        $cache
    );

    $stats = $deployment->optimize();
    
    expect($stats['roles'])->toBeGreaterThan(0);
    expect($cache->get('vima:roles:' . $role->id . ':permissions'))->not->toBeNull();
});

it('clears cache successfully', function () {
    $cache = resolve(CacheInterface::class);
    $cache->set('manual', 1);
    
    $deployment = resolve(DeploymentService::class);
    $deployment->clear();
    
    expect($cache->get('manual'))->toBeNull();
});

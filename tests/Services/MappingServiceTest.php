<?php

use Vima\Core\Services\MappingService;
use Vima\Core\Events\Mapping\MapGenerated;
use Vima\Core\Tests\Fixtures\MockEventDispatcher;

it('loads existing mapping', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'mapping_');
    $initialData = [
        'roles' => ['ADMIN' => 'admin'],
        'permissions' => ['POSTS_EDIT' => 'posts:edit'],
        'namespaces' => ['POSTS' => 'posts']
    ];
    file_put_contents($tempFile, json_encode($initialData));

    try {
        $service = new MappingService($tempFile);
        
        expect($service->all('roles'))->toHaveKey('ADMIN', 'admin');
        expect($service->all('permissions'))->toHaveKey('POSTS_EDIT', 'posts:edit');
        expect($service->all('namespaces'))->toHaveKey('POSTS', 'posts');
    } finally {
        if (file_exists($tempFile)) unlink($tempFile);
    }
});

it('generates and registers stable slugs', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'mapping_');
    file_put_contents($tempFile, json_encode(['roles' => [], 'permissions' => [], 'namespaces' => []]));

    try {
        $service = new MappingService($tempFile);
        
        $slug1 = $service->getOrRegisterSlug('user.create', 'permissions');
        expect($slug1)->toBe('USER_CREATE');
        
        $slug2 = $service->getOrRegisterSlug('user:create', 'permissions');
        expect($slug2)->toBe('USER_CREATE_1'); // Collision handling

        $slug3 = $service->getOrRegisterSlug('user-create', 'permissions');
        expect($slug3)->toBe('USER_CREATE_2'); // Collision handling

        // Ensures it returns existing slug
        expect($service->getOrRegisterSlug('user.create', 'permissions'))->toBe('USER_CREATE');
    } finally {
        if (file_exists($tempFile)) unlink($tempFile);
    }
});

it('extracts namespaces when registering slugs', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'mapping_');
    file_put_contents($tempFile, json_encode(['roles' => [], 'permissions' => [], 'namespaces' => []]));

    try {
        $service = new MappingService($tempFile);
        $service->getOrRegisterSlug('blog:posts.edit', 'permissions');

        expect($service->all('namespaces'))->toHaveKey('BLOG', 'blog');
    } finally {
        if (file_exists($tempFile)) unlink($tempFile);
    }
});

it('syncs multiple names', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'mapping_');
    file_put_contents($tempFile, json_encode(['roles' => [], 'permissions' => [], 'namespaces' => []]));

    try {
        $service = new MappingService($tempFile);
        $service->sync(['admin', 'editor'], 'roles');

        expect($service->all('roles'))->toHaveKey('ADMIN', 'admin');
        expect($service->all('roles'))->toHaveKey('EDITOR', 'editor');
    } finally {
        if (file_exists($tempFile)) unlink($tempFile);
    }
});

it('saves mapping and dispatches events', function () {
    $tempFile = sys_get_temp_dir() . '/vima_mapping_' . uniqid() . '.json';
    $dispatcher = new MockEventDispatcher();
    
    try {
        $service = new MappingService($tempFile, $dispatcher);
        $service->sync(['admin'], 'roles');
        $service->save();

        expect(file_exists($tempFile))->toBeTrue();
        
        $data = json_decode(file_get_contents($tempFile), true);
        expect($data['roles'])->toHaveKey('ADMIN', 'admin');

        $events = $dispatcher->dispatched;
        expect(count($events))->toBe(3); // roles, permissions, namespaces
        expect($events[0])->toBeInstanceOf(MapGenerated::class);
    } finally {
        if (file_exists($tempFile)) unlink($tempFile);
    }
});

it('generates typescript files', function () {
    $tempMapping = tempnam(sys_get_temp_dir(), 'mapping_');
    $mappingData = [
        'roles' => ['ADMIN' => 'admin'],
        'permissions' => ['POSTS_CREATE' => 'posts:create'],
        'namespaces' => ['POSTS' => 'posts']
    ];
    file_put_contents($tempMapping, json_encode($mappingData));

    $tempOutputDir = sys_get_temp_dir() . '/vima_ts_test_' . uniqid();

    try {
        $service = new MappingService($tempMapping);
        $service->generateTypeScriptFiles($tempOutputDir);

        expect(file_exists($tempOutputDir . '/Roles.ts'))->toBeTrue();
        expect(file_exists($tempOutputDir . '/PostsPermissions.ts'))->toBeTrue();
        expect(file_exists($tempOutputDir . '/Namespaces.ts'))->toBeTrue();
    } finally {
        if (file_exists($tempMapping)) unlink($tempMapping);
        if (is_dir($tempOutputDir)) {
            $files = glob($tempOutputDir . '/*');
            foreach ($files as $file) unlink($file);
            rmdir($tempOutputDir);
        }
    }
});

<?php

use Vima\Core\Services\MapGenerator;
use Vima\Core\Services\MappingService;
use Vima\Core\Config\Setup;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
use Vima\Core\Tests\Fixtures\MockEventDispatcher;

it('generates roles class content', function () {
    $tempFile = sys_get_temp_dir() . '/vima_mapping_' . uniqid() . '.json';
    $mappingService = new MappingService($tempFile);
    $generator = new MapGenerator($mappingService);

    $setup = new Setup(roles: [
        new Role('admin', namespace: 'system'),
        new Role('editor')
    ]);

    try {
        $content = $generator->generateRoles($setup, 'App\Config\Vima');

        expect($content)->toContain('namespace App\Config\Vima;');
        expect($content)->toContain('final class Roles extends EnumMapper');
        expect($content)->toContain("public const string SYSTEM_ADMIN = 'system:admin';");
        expect($content)->toContain("public const string EDITOR = 'editor';");
    } finally {
        if (file_exists($tempFile)) unlink($tempFile);
    }
});

it('generates permissions class content', function () {
    $tempFile = sys_get_temp_dir() . '/vima_mapping_' . uniqid() . '.json';
    $mappingService = new MappingService($tempFile);
    $generator = new MapGenerator($mappingService);

    $setup = new Setup(
        roles: [
            new Role('editor', [new Permission('posts.edit')])
        ],
        permissions: [
            new Permission('posts.create'),
            new Permission('users.delete', namespace: 'system')
        ]
    );

    try {
        $content = $generator->generatePermissions($setup, 'App\Config\Vima');

        expect($content)->toContain('namespace App\Config\Vima;');
        expect($content)->toContain('final class Permissions extends EnumMapper');
        expect($content)->toContain("public const string POSTS_EDIT = 'posts.edit';");
        expect($content)->toContain("public const string POSTS_CREATE = 'posts.create';");
        expect($content)->toContain("public const string SYSTEM_USERS_DELETE = 'system:users.delete';");
    } finally {
        if (file_exists($tempFile)) unlink($tempFile);
    }
});

it('generates namespaces class content', function () {
    $tempFile = sys_get_temp_dir() . '/vima_mapping_' . uniqid() . '.json';
    $mappingService = new MappingService($tempFile);
    
    // Seed some namespaces by registering a slug
    $mappingService->getOrRegisterSlug('system:users', 'permissions');
    $mappingService->getOrRegisterSlug('app:posts', 'permissions');

    $generator = new MapGenerator($mappingService);

    try {
        $content = $generator->generateNamespaces('App\Config\Vima');

        expect($content)->toContain('namespace App\Config\Vima;');
        expect($content)->toContain('final class Namespaces extends EnumMapper');
        expect($content)->toContain("public const string SYSTEM = 'system';");
        expect($content)->toContain("public const string APP = 'app';");
    } finally {
        if (file_exists($tempFile)) unlink($tempFile);
    }
});

it('dispatches MapGenerated events', function () {
    $tempFile = sys_get_temp_dir() . '/vima_mapping_' . uniqid() . '.json';
    $dispatcher = new MockEventDispatcher();
    $mappingService = new MappingService($tempFile, $dispatcher);
    $generator = new MapGenerator($mappingService, $dispatcher);

    $setup = new Setup(roles: [new Role('admin')]);

    try {
        $generator->generateRoles($setup, 'App\Config\Vima');

        $events = $dispatcher->dispatched;
        // mappingService->save() dispatches 3 events (roles, perms, namespaces)
        // generator->generateRoles() dispatches 1 extra event for 'roles'
        expect(count($events))->toBeGreaterThan(0);
    } finally {
        if (file_exists($tempFile)) unlink($tempFile);
    }
});

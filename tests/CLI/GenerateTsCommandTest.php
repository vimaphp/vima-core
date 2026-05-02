<?php

use Symfony\Component\Console\Tester\CommandTester;
use Vima\Core\CLI\Commands\GenerateTsCommand;

it('fails if mapping file does not exist', function () {
    $command = new GenerateTsCommand();
    $tester = new CommandTester($command);
    
    $exitCode = $tester->execute(['--mapping' => 'non_existent.json']);
    
    expect($exitCode)->toBe(GenerateTsCommand::FAILURE);
    expect($tester->getDisplay())->toContain('Mapping file not found');
});

it('generates ts files successfully', function () {
    $tempMapping = tempnam(sys_get_temp_dir(), 'mapping_');
    $mappingData = [
        'roles' => ['ADMIN' => 'admin'],
        'permissions' => ['POSTS_CREATE' => 'posts:create'],
        'namespaces' => ['POSTS' => 'posts']
    ];
    file_put_contents($tempMapping, json_encode($mappingData));

    $tempOutputDir = sys_get_temp_dir() . '/vima_ts_test_' . uniqid();
    mkdir($tempOutputDir);

    try {
        $command = new GenerateTsCommand();
        $tester = new CommandTester($command);
        
        $exitCode = $tester->execute([
            '--mapping' => $tempMapping,
            '--output' => $tempOutputDir
        ]);
        
        expect($exitCode)->toBe(GenerateTsCommand::SUCCESS);
        expect($tester->getDisplay())->toContain('TypeScript files generated successfully');

        expect(file_exists($tempOutputDir . '/Roles.ts'))->toBeTrue();
        expect(file_exists($tempOutputDir . '/PostsPermissions.ts'))->toBeTrue();
        expect(file_exists($tempOutputDir . '/Namespaces.ts'))->toBeTrue();

        $rolesContent = file_get_contents($tempOutputDir . '/Roles.ts');
        expect($rolesContent)->toContain('ADMIN: \'admin\'');
        
        $permsContent = file_get_contents($tempOutputDir . '/PostsPermissions.ts');
        expect($permsContent)->toContain('POSTS_CREATE: \'create\'');
    } finally {
        // Cleanup
        if (file_exists($tempMapping)) unlink($tempMapping);
        if (is_dir($tempOutputDir)) {
            $files = glob($tempOutputDir . '/*');
            foreach ($files as $file) unlink($file);
            rmdir($tempOutputDir);
        }
    }
});

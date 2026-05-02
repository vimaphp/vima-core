<?php

use Symfony\Component\Console\Tester\CommandTester;
use Vima\Core\CLI\Commands\SyncCommand;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Config\Setup;
use Vima\Core\Entities\Role;
use Vima\Core\Contracts\RoleRepositoryInterface;
use function Vima\Core\resolve;

beforeEach(function () {
    initDependencies();
});

it('fails if config option is missing', function () {
    $command = new SyncCommand();
    $tester = new CommandTester($command);
    
    $exitCode = $tester->execute([]);
    
    expect($exitCode)->toBe(SyncCommand::FAILURE);
    expect($tester->getDisplay())->toContain('Config path is required');
});

it('fails if config file does not exist', function () {
    $command = new SyncCommand();
    $tester = new CommandTester($command);
    
    $exitCode = $tester->execute(['--config' => '/path/to/nothing.php']);
    
    expect($exitCode)->toBe(SyncCommand::FAILURE);
    expect($tester->getDisplay())->toContain('Config file not found');
});

it('syncs successfully with a valid config', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'vima_config_');
    $configContent = '<?php return new Vima\Core\Config\VimaConfig(setup: new Vima\Core\Config\Setup(roles: [new Vima\Core\Entities\Role("tester")]));';
    file_put_contents($tempFile, $configContent);

    try {
        $command = new SyncCommand();
        $tester = new CommandTester($command);
        
        $exitCode = $tester->execute(['--config' => $tempFile]);
        
        expect($exitCode)->toBe(SyncCommand::SUCCESS);
        expect($tester->getDisplay())->toContain('Sync completed successfully');

        // Verify it actually synced
        $roleRepo = resolve(RoleRepositoryInterface::class);
        expect($roleRepo->findByName('tester'))->not->toBeNull();
    } finally {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
});

it('fails if config file does not return VimaConfig', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'vima_invalid_');
    $configContent = '<?php return (object)["not" => "vima"];';
    file_put_contents($tempFile, $configContent);

    try {
        $command = new SyncCommand();
        $tester = new CommandTester($command);
        
        $exitCode = $tester->execute(['--config' => $tempFile]);
        
        expect($exitCode)->toBe(SyncCommand::FAILURE);
        expect($tester->getDisplay())->toContain('Config file must return an instance of Vima\Core\Config\VimaConfig');
    } finally {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
});

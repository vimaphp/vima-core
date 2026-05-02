<?php

use Symfony\Component\Console\Tester\CommandTester;
use Vima\Core\CLI\Commands\MakeRoleCommand;
use Vima\Core\CLI\Commands\MakePermissionCommand;
use Vima\Core\CLI\Commands\AssignRoleCommand;
use Vima\Core\CLI\Commands\RevokeRoleCommand;
use Vima\Core\CLI\Commands\GrantPermissionCommand;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\AccessManagerInterface;
use function Vima\Core\resolve;

beforeEach(function () {
    initDependencies();
});

it('creates a role', function () {
    $command = new MakeRoleCommand();
    $tester = new CommandTester($command);
    
    $exitCode = $tester->execute([
        'name' => 'manager',
        'description' => 'Manages things'
    ]);
    
    expect($exitCode)->toBe(MakeRoleCommand::SUCCESS);
    expect($tester->getDisplay())->toContain('Role [manager] created successfully');

    $repo = resolve(RoleRepositoryInterface::class);
    expect($repo->findByName('manager'))->not->toBeNull();
});

it('creates a permission', function () {
    $command = new MakePermissionCommand();
    $tester = new CommandTester($command);
    
    $exitCode = $tester->execute([
        'name' => 'reports.view'
    ]);
    
    expect($exitCode)->toBe(MakePermissionCommand::SUCCESS);
    expect($tester->getDisplay())->toContain('Permission [reports.view] created successfully');

    $repo = resolve(PermissionRepositoryInterface::class);
    expect($repo->findByName('reports.view'))->not->toBeNull();
});

it('assigns and revokes a role', function () {
    $manager = resolve(AccessManagerInterface::class);
    $manager->addRole('admin');

    $assignCmd = new AssignRoleCommand();
    $tester = new CommandTester($assignCmd);
    $tester->execute(['user_id' => 1, 'role' => 'admin']);
    
    expect($tester->getDisplay())->toContain('Role [admin] assigned to user [1] successfully');
    expect($manager->hasRole(new UserMock(1), 'admin'))->toBeTrue();

    $revokeCmd = new RevokeRoleCommand();
    $tester = new CommandTester($revokeCmd);
    $tester->execute(['user_id' => 1, 'role' => 'admin']);
    
    expect($tester->getDisplay())->toContain('Role [admin] revoked from user [1] successfully');
    expect($manager->hasRole(new UserMock(1), 'admin'))->toBeFalse();
});

it('grants a direct permission', function () {
    $manager = resolve(AccessManagerInterface::class);
    $manager->addPermission('debug.access');

    $command = new GrantPermissionCommand();
    $tester = new CommandTester($command);
    $tester->execute(['user_id' => 1, 'permission' => 'debug.access']);
    
    expect($tester->getDisplay())->toContain('Permission [debug.access] granted to user [1] successully');
    expect($manager->can(new UserMock(1), 'debug.access'))->toBeTrue();
});

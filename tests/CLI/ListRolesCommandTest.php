<?php

use Symfony\Component\Console\Tester\CommandTester;
use Vima\Core\CLI\Commands\ListRolesCommand;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
use Vima\Core\Contracts\AccessManagerInterface;
use function Vima\Core\resolve;

beforeEach(function () {
    initDependencies();
});

it('shows no roles found message if empty', function () {
    $command = new ListRolesCommand();
    $tester = new CommandTester($command);
    
    $exitCode = $tester->execute([]);
    
    expect($exitCode)->toBe(ListRolesCommand::SUCCESS);
    expect($tester->getDisplay())->toContain('No roles found.');
});

it('lists roles in a table', function () {
    /** @var AccessManagerInterface $manager */
    $manager = resolve(AccessManagerInterface::class);
    
    $manager->addRole('admin', ['all']);
    $manager->addRole('editor', ['edit'], description: 'Can edit stuff');

    $command = new ListRolesCommand();
    $tester = new CommandTester($command);
    
    $exitCode = $tester->execute([]);
    
    expect($exitCode)->toBe(ListRolesCommand::SUCCESS);
    $output = $tester->getDisplay();
    
    expect($output)->toContain('admin');
    expect($output)->toContain('all');
    expect($output)->toContain('editor');
    expect($output)->toContain('edit');
    expect($output)->toContain('Can edit stuff');
});

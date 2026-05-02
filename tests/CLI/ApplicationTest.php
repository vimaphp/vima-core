<?php

use Vima\Core\CLI\Application;

it('registers all commands', function () {
    $app = new Application();
    
    $commands = [
        'vima:sync',
        'make:role',
        'make:permission',
        'role:list',
        'role:assign',
        'role:revoke',
        'permission:grant',
        'vima:generate:ts'
    ];

    foreach ($commands as $name) {
        expect($app->has($name))->toBeTrue("Command [{$name}] is not registered in the Application.");
    }
});

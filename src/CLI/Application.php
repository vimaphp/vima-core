<?php

namespace Vima\Core\CLI;

use Symfony\Component\Console\Application as ConsoleApp;
use Vima\Core\CLI\Commands\SyncCommand;

class Application extends ConsoleApp
{
    public function __construct()
    {
        parent::__construct('Vima RBAC/ABAC CLI', '0.1.0');

        $this->add(new SyncCommand());
    }
}

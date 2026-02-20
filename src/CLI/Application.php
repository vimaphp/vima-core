<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Vima\Core\CLI;

use Symfony\Component\Console\Application as ConsoleApp;
use Vima\Core\CLI\Commands\SyncCommand;
use Vima\Core\CLI\Commands\MakeRoleCommand;
use Vima\Core\CLI\Commands\MakePermissionCommand;
use Vima\Core\CLI\Commands\ListRolesCommand;
use Vima\Core\CLI\Commands\AssignRoleCommand;
use Vima\Core\CLI\Commands\RevokeRoleCommand;
use Vima\Core\CLI\Commands\GrantPermissionCommand;

class Application extends ConsoleApp
{
    public function __construct()
    {
        parent::__construct('Vima RBAC/ABAC CLI', '0.1.0');

        $this->addCommands([
            new SyncCommand(),
            new MakeRoleCommand(),
            new MakePermissionCommand(),
            new ListRolesCommand(),
            new AssignRoleCommand(),
            new RevokeRoleCommand(),
            new GrantPermissionCommand(),
        ]);
    }
}

<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Vima\Core\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vima\Core\Contracts\AccessManagerInterface;
use function Vima\Core\resolve;

/**
 * Class GrantPermissionCommand
 * 
 * CLI command to grant a direct permission to a specific user.
 */
final class GrantPermissionCommand extends Command
{
    public function getName(): string|null
    {
        return 'permission:grant';
    }

    public function getDescription(): string
    {
        return 'Grant a direct permission to a user';
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user_id', InputArgument::REQUIRED, 'The ID of the user')
            ->addArgument('permission', InputArgument::REQUIRED, 'The name of the permission');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $userId = $input->getArgument('user_id');
        $permissionName = $input->getArgument('permission');

        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);

        try {
            $user = new class ($userId) {
                public function __construct(public $id)
                {}
                public function vimaGetId()
                {
                    return $this->id; }
            };

            $manager->grantPermission($user, $permissionName);
            $output->writeln("<info>Permission [{$permissionName}] granted to user [{$userId}] successully.</info>");
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to grant permission: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

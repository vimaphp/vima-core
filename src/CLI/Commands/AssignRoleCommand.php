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
 * Class AssignRoleCommand
 * 
 * CLI command to assign a role to a specific user.
 */
final class AssignRoleCommand extends Command
{
    public function getName(): string|null
    {
        return 'role:assign';
    }

    public function getDescription(): string
    {
        return 'Assign a role to a user';
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user_id', InputArgument::REQUIRED, 'The ID of the user')
            ->addArgument('role', InputArgument::REQUIRED, 'The name of the role');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $userId = $input->getArgument('user_id');
        $roleName = $input->getArgument('role');

        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);

        try {
            // Create a fake user object for resolution
            $user = new class ($userId) {
                public function __construct(public $id)
                {}
                public function vimaGetId()
                {
                    return $this->id; }
            };

            $manager->assignRole($user, $roleName);
            $output->writeln("<info>Role [{$roleName}] assigned to user [{$userId}] successfully.</info>");
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to assign role: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

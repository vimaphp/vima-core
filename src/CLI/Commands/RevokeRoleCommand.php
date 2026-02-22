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
 * Class RevokeRoleCommand
 * 
 * CLI command to revoke a role from a specific user.
 */
final class RevokeRoleCommand extends Command
{
    public function getName(): string|null
    {
        return 'role:revoke';
    }

    public function getDescription(): string
    {
        return 'Revoke a role from a user';
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
            $user = new class ($userId) {
                public function __construct(public $id)
                {}
                public function vimaGetId()
                {
                    return $this->id; }
            };

            $manager->detachRole($user, $roleName);
            $output->writeln("<info>Role [{$roleName}] revoked from user [{$userId}] successfully.</info>");
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to revoke role: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

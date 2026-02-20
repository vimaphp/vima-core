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
 * Class MakeRoleCommand
 * 
 * CLI command to create a new role manually.
 */
final class MakeRoleCommand extends Command
{
    public function getName(): string|null
    {
        return 'make:role';
    }

    public function getDescription(): string
    {
        return 'Create a new role';
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the role')
            ->addArgument('description', InputArgument::OPTIONAL, 'The description of the role');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $description = $input->getArgument('description');

        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);

        try {
            $role = $manager->addRole($name, $description);
            $output->writeln("<info>Role [{$role->name}] created successfully.</info>");
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to create role: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

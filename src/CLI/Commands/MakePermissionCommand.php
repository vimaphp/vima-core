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
 * Class MakePermissionCommand
 * 
 * CLI command to create a new permission manually.
 */
final class MakePermissionCommand extends Command
{
    public function getName(): string|null
    {
        return 'make:permission';
    }


    public function getDescription(): string
    {
        return 'Create a new permission';
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the permission')
            ->addArgument('description', InputArgument::OPTIONAL, 'The description of the permission');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $description = $input->getArgument('description');

        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);

        try {
            $permission = $manager->addPermission($name, $description);
            $output->writeln("<info>Permission [{$permission->name}] created successfully.</info>");
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to create permission: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

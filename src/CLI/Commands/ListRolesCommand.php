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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Vima\Core\Contracts\RoleRepositoryInterface;
use function Vima\Core\resolve;

/**
 * Class ListRolesCommand
 * 
 * CLI command to list all defined roles and their permissions.
 */
final class ListRolesCommand extends Command
{
    public function getName(): string|null
    {
        return 'role:list';
    }

    public function getDescription(): string
    {
        return 'List all roles';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var RoleRepositoryInterface $repository */
        $repository = resolve(RoleRepositoryInterface::class);
        $roles = $repository->all();

        if (empty($roles)) {
            $output->writeln('<comment>No roles found.</comment>');
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['ID', 'Name', 'Description', 'Permissions']);

        foreach ($roles as $role) {
            $perms = array_map(fn($p) => $p->name, $role->permissions);
            $table->addRow([
                $role->id ?? 'N/A',
                $role->name,
                $role->description ?? '-',
                implode(', ', $perms)
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}

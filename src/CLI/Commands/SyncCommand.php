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

final class SyncCommand extends Command
{
    public function getName(): string|null
    {
        return 'vima:sync';
    }

    public function getDescription(): string
    {
        return 'Sync permissions and roles from config to storage';
    }

    protected function configure(): void
    {
        $this
            ->addOption('config', 'c', \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, 'Path to the vima config file');
    }


    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $configPath = $input->getOption('config');

        if (!$configPath) {
            $output->writeln('<error>Config path is required. Use --config or -c.</error>');
            return Command::FAILURE;
        }

        if (!file_exists($configPath)) {
            $output->writeln("<error>Config file not found at [$configPath]</error>");
            return Command::FAILURE;
        }

        /** @var \Vima\Core\Config\VimaConfig $config */
        $config = require $configPath;

        if (!$config instanceof \Vima\Core\Config\VimaConfig) {
            $output->writeln('<error>Config file must return an instance of Vima\Core\Config\VimaConfig</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Syncing permissions and roles...</info>');

        try {
            /** @var \Vima\Core\Services\SyncService $syncService */
            $syncService = \Vima\Core\resolve(\Vima\Core\Services\SyncService::class);
            $syncService->sync($config);

            $output->writeln('<info>Sync completed successfully!</info>');
        } catch (\Throwable $e) {
            $output->writeln('<error>Sync failed: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Vima\Core\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Vima\Core\Services\DeploymentService;
use function Vima\Core\resolve;

/**
 * Class ClearCacheCommand
 * 
 * Flushes all Vima caches.
 *
 * @package Vima\Core\CLI\Commands
 */
final class ClearCacheCommand extends Command
{
    public function getName(): string|null
    {
        return 'vima:clear';
    }

    public function getDescription(): string
    {
        return 'Flush all Vima caches';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        /** @var DeploymentService $service */
        $service = resolve(DeploymentService::class);

        try {
            $service->clear();
            $io->success('Vima cache cleared successfully.');
        } catch (\Throwable $e) {
            $io->error('Failed to clear cache: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

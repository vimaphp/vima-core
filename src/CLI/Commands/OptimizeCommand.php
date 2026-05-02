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
 * Class OptimizeCommand
 * 
 * Pre-warms Vima caches for production environments.
 *
 * @package Vima\Core\CLI\Commands
 */
final class OptimizeCommand extends Command
{
    public function getName(): string|null
    {
        return 'vima:optimize';
    }

    public function getDescription(): string
    {
        return 'Optimize Vima performance by pre-warming caches';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Vima Optimization');

        /** @var DeploymentService $service */
        $service = resolve(DeploymentService::class);

        try {
            $io->text('Warming up caches...');
            $stats = $service->optimize();

            $io->success(sprintf(
                'Optimization complete! Cached %d roles and %d policy maps.',
                $stats['roles'],
                $stats['policies']
            ));
        } catch (\Throwable $e) {
            $io->error('Optimization failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

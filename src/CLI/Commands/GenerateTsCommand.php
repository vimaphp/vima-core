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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vima\Core\Services\MappingService;

/**
 * Class GenerateTsCommand
 * 
 * CLI command to generate TypeScript types and constants from the mapping file.
 */
class GenerateTsCommand extends Command
{
    public function getName(): string|null
    {
        return 'vima:generate:ts';
    }

    public function getDescription(): string
    {
        return 'Generate TypeScript types and constants for roles and permissions';
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Directory to output the TS files', './types')
            ->addOption('mapping', 'm', InputOption::VALUE_REQUIRED, 'Path to the mapping.json file', './mapping.json');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $outputDir = $input->getOption('output');
        $mappingFile = $input->getOption('mapping');

        if (!file_exists($mappingFile)) {
            $output->writeln("<error>Mapping file not found at [{$mappingFile}]</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Generating TypeScript files in [{$outputDir}] using [{$mappingFile}]...</info>");

        try {
            $mappingService = new MappingService($mappingFile);
            $mappingService->generateTypeScriptFiles($outputDir);

            $output->writeln('<info>TypeScript files generated successfully!</info>');
            $output->writeln("<comment>Files generated in: {$outputDir}</comment>");
        } catch (\Throwable $e) {
            $output->writeln('<error>Generation failed: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

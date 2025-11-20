<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrations:sync',
    description: 'Synchronize migrations table with actual database state',
)]
class MigrationsSyncCommand extends Command
{
    public function __construct(
        private readonly DependencyFactory $dependencyFactory,
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be done without executing')
            ->addOption('mark-all', null, InputOption::VALUE_NONE, 'Mark all pending migrations as executed without checking')
            ->setHelp(<<<'HELP'
This command helps synchronize the migrations tracking table with the actual database state.

It checks each pending migration to see if its changes are already applied to the database,
and offers to mark them as executed.

Usage:
  # Dry run to see what would be done
  php bin/console app:migrations:sync --dry-run --env=prod

  # Interactive mode - checks each migration
  php bin/console app:migrations:sync --env=prod

  # Mark all pending migrations as executed (use with caution!)
  php bin/console app:migrations:sync --mark-all --env=prod
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);
        $dryRun  = $input->getOption('dry-run');
        $markAll = $input->getOption('mark-all');

        $io->title('Migration Synchronization Tool');

        // Get pending migrations
        $planCalculator = $this->dependencyFactory->getMigrationPlanCalculator();
        $plan           = $planCalculator->getPlanForVersions(
            $this->dependencyFactory->getMigrationRepository()->getMigrations()->getItems(),
        );

        $statusCalculator    = $this->dependencyFactory->getMigrationStatusCalculator();
        $executedMigrations  = $statusCalculator->getExecutedMigrations();
        $availableMigrations = $statusCalculator->getAvailableMigrations();

        $pendingMigrations = array_diff(
            array_map(fn ($v) => (string) $v, $availableMigrations->getItems()),
            array_map(fn ($v) => (string) $v, $executedMigrations->getItems()),
        );

        if (empty($pendingMigrations)) {
            $io->success('No pending migrations found. Database is up to date!');

            return Command::SUCCESS;
        }

        $io->section('Pending Migrations');
        $io->listing($pendingMigrations);

        if ($markAll) {
            if (!$dryRun && !$io->confirm('Are you sure you want to mark ALL pending migrations as executed?', false)) {
                $io->warning('Operation cancelled.');

                return Command::SUCCESS;
            }

            $metadataStorage = $this->dependencyFactory->getMetadataStorage();
            $marked          = 0;

            foreach ($pendingMigrations as $versionString) {
                $version = new Version($versionString);

                if ($dryRun) {
                    $io->writeln("Would mark: {$versionString}");
                } else {
                    $metadataStorage->complete($this->dependencyFactory->getVersionExecutionResult($version, 0));
                    $io->writeln("<info>✓</info> Marked as executed: {$versionString}");
                }
                ++$marked;
            }

            if ($dryRun) {
                $io->note("DRY RUN: Would mark {$marked} migrations as executed");
            } else {
                $io->success("Successfully marked {$marked} migrations as executed");
            }

            return Command::SUCCESS;
        }

        // Interactive mode - check each migration
        $io->section('Checking Database State');

        $schemaManager = $this->connection->createSchemaManager();
        $tables        = $schemaManager->listTableNames();

        $io->writeln('Found '.count($tables).' tables in database');
        $io->newLine();

        $toMark = [];

        foreach ($pendingMigrations as $versionString) {
            $io->section("Analyzing: {$versionString}");

            // Try to detect if migration is already applied by reading the migration file
            $migrationClass = 'DoctrineMigrations\\'.basename($versionString);
            $migrationPath  = $this->getMigrationPath($versionString);

            if (!file_exists($migrationPath)) {
                $io->warning("Migration file not found: {$migrationPath}");
                continue;
            }

            $content     = file_get_contents($migrationPath);
            $description = $this->extractDescription($content);

            if ($description) {
                $io->writeln("Description: <comment>{$description}</comment>");
            }

            // Show a snippet of what the migration does
            $snippet = $this->extractMigrationSnippet($content);
            if ($snippet) {
                $io->writeln('Changes:');
                $io->writeln($snippet);
            }

            $io->newLine();

            if ($io->confirm('Is this migration already applied to the database?', false)) {
                $toMark[] = $versionString;
            }
        }

        if (empty($toMark)) {
            $io->info('No migrations to mark as executed.');

            return Command::SUCCESS;
        }

        $io->section('Summary');
        $io->writeln('The following migrations will be marked as executed:');
        $io->listing($toMark);

        if ($dryRun) {
            $io->note('DRY RUN: No changes will be made');

            return Command::SUCCESS;
        }

        if (!$io->confirm('Proceed with marking these migrations as executed?', true)) {
            $io->warning('Operation cancelled.');

            return Command::SUCCESS;
        }

        $metadataStorage = $this->dependencyFactory->getMetadataStorage();

        foreach ($toMark as $versionString) {
            $version = new Version($versionString);
            $metadataStorage->complete($this->dependencyFactory->getVersionExecutionResult($version, 0));
            $io->writeln("<info>✓</info> Marked: {$versionString}");
        }

        $io->success('Successfully synchronized migrations table!');
        $io->note('You can now run: php bin/console doctrine:migrations:migrate');

        return Command::SUCCESS;
    }

    private function getMigrationPath(string $versionString): string
    {
        $projectDir = $this->getApplication()->getKernel()->getProjectDir();

        return $projectDir.'/migrations/'.basename($versionString).'.php';
    }

    private function extractDescription(string $content): ?string
    {
        if (preg_match('/public function getDescription\(\): string\s*{\s*return [\'"](.+?)[\'"];/s', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractMigrationSnippet(string $content): ?string
    {
        // Extract SQL from the up() method
        if (preg_match('/public function up\(.*?\): void\s*{(.*?)}/s', $content, $matches)) {
            $upMethod = $matches[1];

            // Extract table names from CREATE TABLE, ALTER TABLE, DROP TABLE
            $operations = [];

            if (preg_match_all('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?(\w+)/i', $upMethod, $tableMatches)) {
                foreach ($tableMatches[1] as $table) {
                    $operations[] = "  • Create table: <info>{$table}</info>";
                }
            }

            if (preg_match_all('/ALTER TABLE\s+(\w+)/i', $upMethod, $tableMatches)) {
                foreach (array_unique($tableMatches[1]) as $table) {
                    $operations[] = "  • Alter table: <comment>{$table}</comment>";
                }
            }

            if (preg_match_all('/DROP TABLE\s+(?:IF EXISTS\s+)?(\w+)/i', $upMethod, $tableMatches)) {
                foreach ($tableMatches[1] as $table) {
                    $operations[] = "  • Drop table: <error>{$table}</error>";
                }
            }

            if (preg_match_all('/ADD\s+(\w+)\s+/i', $upMethod, $colMatches)) {
                $columns = array_unique($colMatches[1]);
                if (count($columns) <= 5) {
                    foreach ($columns as $col) {
                        $operations[] = "  • Add column: <info>{$col}</info>";
                    }
                } else {
                    $operations[] = '  • Add '.count($columns).' columns';
                }
            }

            if (preg_match_all('/DROP\s+(\w+)(?:\s|,|;)/i', $upMethod, $colMatches)) {
                foreach (array_unique($colMatches[1]) as $col) {
                    if (!in_array(strtoupper($col), ['TABLE', 'INDEX', 'CONSTRAINT'])) {
                        $operations[] = "  • Drop column: <error>{$col}</error>";
                    }
                }
            }

            if (preg_match_all('/CREATE\s+(?:UNIQUE\s+)?INDEX\s+(\w+)/i', $upMethod, $indexMatches)) {
                foreach ($indexMatches[1] as $index) {
                    $operations[] = "  • Create index: <info>{$index}</info>";
                }
            }

            return empty($operations) ? '  <comment>(Complex migration - check manually)</comment>' : implode("\n", $operations);
        }

        return null;
    }
}

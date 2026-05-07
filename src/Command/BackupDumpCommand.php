<?php

declare(strict_types=1);

namespace App\Command;

use function dirname;

use Doctrine\DBAL\Connection;

use function is_bool;
use function is_float;
use function is_int;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * Dumps the current database to a file.
 *
 * Supports MariaDB / MySQL via mysqldump, PostgreSQL via pg_dump
 * and SQLite via PHP's PDO ".dump"-equivalent (no shell sqlite3 dependency).
 *
 * TEST-006 (sprint-004) — backup/restore strategy.
 */
#[AsCommand(
    name: 'app:backup:dump',
    description: 'Dump the current database to a file (multi-driver: mysql/pgsql/sqlite).',
)]
final class BackupDumpCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Output file path. Defaults to var/backups/backup-<timestamp>.<ext>.',
        )->addOption('compress', 'c', InputOption::VALUE_NONE, 'Gzip the dump (mysql/pgsql only).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $params = $this->connection->getParams();
        $driver = $this->resolveDriver($params);

        $outputPath = (string) (
            $input->getOption('output') ?? $this->defaultOutputPath($driver, (bool) $input->getOption('compress'))
        );

        $dir = dirname($outputPath);
        if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
            $io->error(sprintf('Cannot create backup directory: %s', $dir));

            return Command::FAILURE;
        }

        $io->title(sprintf('Backup dump (%s)', $driver));
        $io->text(sprintf('Output: %s', $outputPath));

        $exit = match ($driver) {
            'mysql', 'mariadb' => $this->dumpMysql($params, $outputPath, (bool) $input->getOption('compress'), $io),
            'pgsql' => $this->dumpPgsql($params, $outputPath, (bool) $input->getOption('compress'), $io),
            'sqlite' => $this->dumpSqlite($params, $outputPath, $io),
            default => $this->unsupportedDriver($driver, $io),
        };

        if ($exit === Command::SUCCESS) {
            $size = is_file($outputPath) ? filesize($outputPath) : 0;
            $io->success(sprintf(
                'Dump created (%s bytes): %s',
                number_format((float) $size, 0, '.', ' '),
                $outputPath,
            ));
        }

        return $exit;
    }

    private function resolveDriver(array $params): string
    {
        $driver = $params['driver'] ?? '';

        return match ($driver) {
            'pdo_mysql', 'mysqli' => str_contains((string) ($params['serverVersion'] ?? ''), 'mariadb')
                ? 'mariadb'
                : 'mysql',
            'pdo_pgsql', 'pgsql' => 'pgsql',
            'pdo_sqlite', 'sqlite3' => 'sqlite',
            default => $driver,
        };
    }

    private function defaultOutputPath(string $driver, bool $compress): string
    {
        $ext = match ($driver) {
            'sqlite' => 'sql',
            default => $compress ? 'sql.gz' : 'sql',
        };

        return sprintf('%s/var/backups/backup-%s.%s', $this->projectDir, date('Ymd-His'), $ext);
    }

    private function dumpMysql(array $params, string $outputPath, bool $compress, SymfonyStyle $io): int
    {
        $cmd = [
            'mysqldump',
            '--host='.($params['host'] ?? '127.0.0.1'),
            '--port='.($params['port'] ?? '3306'),
            '--user='.($params['user'] ?? 'root'),
            '--single-transaction',
            '--quick',
            '--no-tablespaces',
            $params['dbname'] ?? throw new RuntimeException('Missing dbname'),
        ];

        $env = isset($params['password']) ? ['MYSQL_PWD' => $params['password']] : [];

        return $this->runDumpProcess($cmd, $outputPath, $compress, $env, $io);
    }

    private function dumpPgsql(array $params, string $outputPath, bool $compress, SymfonyStyle $io): int
    {
        $cmd = [
            'pg_dump',
            '--host='.($params['host'] ?? '127.0.0.1'),
            '--port='.($params['port'] ?? '5432'),
            '--username='.($params['user'] ?? 'postgres'),
            '--no-password',
            '--clean',
            '--if-exists',
            $params['dbname'] ?? throw new RuntimeException('Missing dbname'),
        ];

        $env = isset($params['password']) ? ['PGPASSWORD' => $params['password']] : [];

        return $this->runDumpProcess($cmd, $outputPath, $compress, $env, $io);
    }

    /**
     * SQLite dump using a pure PHP backup (PDO copy + schema/data SQL export).
     * Avoids dependency on the sqlite3 CLI which may not be installed in production.
     */
    private function dumpSqlite(array $params, string $outputPath, SymfonyStyle $io): int
    {
        $sourcePath = (string) ($params['path'] ?? $params['url'] ?? '');
        if ($sourcePath === '' || !is_file($sourcePath)) {
            $io->error(sprintf('SQLite database file not found: %s', $sourcePath));

            return Command::FAILURE;
        }

        $sql = "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n";

        $platform = $this->connection->getDatabasePlatform();
        $schemaManager = $this->connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        foreach ($tables as $table) {
            $createRow = $this->connection->fetchAssociative('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', [
                'table',
                $table,
            ]);
            if ($createRow !== false && isset($createRow['sql'])) {
                $sql .= $createRow['sql'].";\n";
            }

            $rows = $this->connection->fetchAllAssociative('SELECT * FROM '.$platform->quoteSingleIdentifier($table));
            foreach ($rows as $row) {
                $columns = array_map(static fn ($col) => $platform->quoteSingleIdentifier(
                    (string) $col,
                ), array_keys($row));
                $values = array_map(fn ($value) => $this->quoteSqliteValue($value), array_values($row));
                $sql .= sprintf(
                    "INSERT INTO %s (%s) VALUES (%s);\n",
                    $platform->quoteSingleIdentifier($table),
                    implode(', ', $columns),
                    implode(', ', $values),
                );
            }
        }

        $sql .= "COMMIT;\n";

        if (file_put_contents($outputPath, $sql) === false) {
            $io->error('Failed to write SQLite dump');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function quoteSqliteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $this->connection->quote((string) $value);
    }

    private function runDumpProcess(array $cmd, string $outputPath, bool $compress, array $env, SymfonyStyle $io): int
    {
        $process = new Process($cmd, env: $env);
        $process->setTimeout(600);

        $handle = $compress ? gzopen($outputPath, 'wb9') : fopen($outputPath, 'wb');
        if ($handle === false) {
            $io->error(sprintf('Cannot open output file: %s', $outputPath));

            return Command::FAILURE;
        }

        $writer = $compress
            ? static fn (string $chunk) => gzwrite($handle, $chunk)
            : static fn (string $chunk) => fwrite($handle, $chunk);

        $process->run(static function (string $type, string $buffer) use ($writer, $io): void {
            if ($type === Process::OUT) {
                $writer($buffer);
            } else {
                $io->writeln('<comment>'.rtrim($buffer).'</comment>');
            }
        });

        $compress ? gzclose($handle) : fclose($handle);

        if (!$process->isSuccessful()) {
            $io->error('Dump command failed: '.$process->getErrorOutput());
            @unlink($outputPath);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function unsupportedDriver(string $driver, SymfonyStyle $io): int
    {
        $io->error(sprintf('Unsupported database driver: %s', $driver));

        return Command::FAILURE;
    }
}

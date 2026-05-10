<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use RuntimeException;

use function strlen;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Restores a database from a dump produced by app:backup:dump.
 *
 * Refuses to run against the prod environment unless --force is provided
 * (since restoring will drop / overwrite existing data).
 *
 * TEST-006 (sprint-004) — backup/restore strategy.
 */
#[AsCommand(
    name: 'app:backup:restore',
    description: 'Restore a database from a dump file (multi-driver: mysql/pgsql/sqlite).',
)]
final class BackupRestoreCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $kernelEnvironment,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Dump file path (.sql or .sql.gz).')->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Allow running against the prod environment.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->kernelEnvironment === 'prod' && !$input->getOption('force')) {
            $io->error('Refusing to restore in prod environment without --force.');

            return Command::FAILURE;
        }

        $file = (string) $input->getArgument('file');
        if (!is_file($file)) {
            $io->error(sprintf('Dump file not found: %s', $file));

            return Command::FAILURE;
        }

        $params = $this->connection->getParams();
        $driver = $this->resolveDriver($params);

        $io->title(sprintf('Backup restore (%s)', $driver));
        $io->text(sprintf('Source: %s', $file));

        return match ($driver) {
            'mysql', 'mariadb' => $this->restoreMysql($params, $file, $io),
            'pgsql' => $this->restorePgsql($params, $file, $io),
            'sqlite' => $this->restoreSqlite($params, $file, $io),
            default => $this->unsupportedDriver($driver, $io),
        };
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

    private function restoreMysql(array $params, string $file, SymfonyStyle $io): int
    {
        $cmd = [
            'mysql',
            '--host='.($params['host'] ?? '127.0.0.1'),
            '--port='.($params['port'] ?? '3306'),
            '--user='.($params['user'] ?? 'root'),
            $params['dbname'] ?? throw new RuntimeException('Missing dbname'),
        ];

        $env = isset($params['password']) ? ['MYSQL_PWD' => $params['password']] : [];

        return $this->runRestoreProcess($cmd, $file, $env, $io);
    }

    private function restorePgsql(array $params, string $file, SymfonyStyle $io): int
    {
        $cmd = [
            'psql',
            '--host='.($params['host'] ?? '127.0.0.1'),
            '--port='.($params['port'] ?? '5432'),
            '--username='.($params['user'] ?? 'postgres'),
            '--no-password',
            '--quiet',
            '--dbname='.($params['dbname'] ?? throw new RuntimeException('Missing dbname')),
        ];

        $env = isset($params['password']) ? ['PGPASSWORD' => $params['password']] : [];

        return $this->runRestoreProcess($cmd, $file, $env, $io);
    }

    private function restoreSqlite(array $params, string $file, SymfonyStyle $io): int
    {
        $targetPath = (string) ($params['path'] ?? $params['url'] ?? '');
        if ($targetPath === '') {
            $io->error('SQLite database path not configured');

            return Command::FAILURE;
        }

        $platform = $this->connection->getDatabasePlatform();
        $tables = $this->connection->createSchemaManager()->listTableNames();

        $this->connection->executeStatement('PRAGMA foreign_keys=OFF');
        foreach ($tables as $table) {
            $this->connection->executeStatement('DROP TABLE IF EXISTS '.$platform->quoteSingleIdentifier($table));
        }

        $sql = (string) file_get_contents($file);
        try {
            foreach ($this->splitSqliteStatements($sql) as $statement) {
                if ($statement === '') {
                    // Skip BEGIN/COMMIT/ROLLBACK: the surrounding caller (or DAMA test bundle)
                    // may already hold an active transaction, and SQLite forbids nesting.
                    continue;
                }
                if ($this->isTransactionControlStatement($statement)) {
                    // Skip BEGIN/COMMIT/ROLLBACK: the surrounding caller (or DAMA test bundle)
                    // may already hold an active transaction, and SQLite forbids nesting.
                    continue;
                }
                $this->connection->executeStatement($statement);
            }
        } catch (Throwable $e) {
            $io->error('SQLite restore failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('SQLite database restored: %s', $targetPath));

        return Command::SUCCESS;
    }

    private function isTransactionControlStatement(string $statement): bool
    {
        $normalized = strtoupper(trim($statement));

        return
            str_starts_with($normalized, 'BEGIN')
            || str_starts_with($normalized, 'COMMIT')
            || str_starts_with($normalized, 'ROLLBACK')
            || str_starts_with($normalized, 'END TRANSACTION')
        ;
    }

    /**
     * Splits an SQL dump on terminating semicolons that are NOT inside string literals.
     *
     * @return iterable<string>
     */
    private function splitSqliteStatements(string $sql): iterable
    {
        $buffer = '';
        $inString = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; ++$i) {
            $char = $sql[$i];

            if ($char === "'") {
                if ($inString && ($i + 1) < $length && $sql[$i + 1] === "'") {
                    // Escaped quote inside string literal.
                    $buffer .= "''";
                    ++$i;
                    continue;
                }
                $inString = !$inString;
            }

            if ($char === ';' && !$inString) {
                yield trim($buffer);
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            yield $tail;
        }
    }

    private function runRestoreProcess(array $cmd, string $file, array $env, SymfonyStyle $io): int
    {
        $isGz = str_ends_with($file, '.gz');
        $handle = $isGz ? gzopen($file, 'rb') : fopen($file, 'rb');
        if ($handle === false) {
            $io->error('Cannot read dump file');

            return Command::FAILURE;
        }

        $process = new Process($cmd, env: $env);
        $process->setTimeout(600);
        $process->setInput(
            (function () use ($handle, $isGz) {
                while (!($isGz ? gzeof($handle) : feof($handle))) {
                    $chunk = $isGz ? gzread($handle, 8192) : fread($handle, 8192);
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    yield $chunk;
                }
                $isGz ? gzclose($handle) : fclose($handle);
            })(),
        );

        $process->run(static function (string $type, string $buffer) use ($io): void {
            if ($type === Process::ERR) {
                $io->writeln('<comment>'.rtrim($buffer).'</comment>');
            }
        });

        if (!$process->isSuccessful()) {
            $io->error('Restore command failed: '.$process->getErrorOutput());

            return Command::FAILURE;
        }

        $io->success('Database restored.');

        return Command::SUCCESS;
    }

    private function unsupportedDriver(string $driver, SymfonyStyle $io): int
    {
        $io->error(sprintf('Unsupported database driver: %s', $driver));

        return Command::FAILURE;
    }
}

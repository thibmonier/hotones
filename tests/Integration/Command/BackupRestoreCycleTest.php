<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration test for the backup/restore cycle (TEST-006, sprint-004).
 *
 * Asserts that:
 *   1. `app:backup:dump` produces a non-empty SQL dump
 *   2. After wiping the data, `app:backup:restore` rebuilds the rows identically
 *
 * Driver under test: SQLite (test env). MySQL/PgSQL paths share the same code path
 * but require shell tooling and a running server, so they are smoke-checked at
 * staging time via the GitHub Actions cron.
 */
final class BackupRestoreCycleTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private Connection $connection;
    private string $dumpPath;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::getContainer()->get(Connection::class);

        $this->connection->executeStatement('DROP TABLE IF EXISTS backup_restore_cycle_fixture');
        $this->connection->executeStatement(
            'CREATE TABLE backup_restore_cycle_fixture (
                id INTEGER PRIMARY KEY,
                label TEXT NOT NULL,
                amount NUMERIC NOT NULL
            )',
        );
        $this->connection->executeStatement(
            "INSERT INTO backup_restore_cycle_fixture (id, label, amount) VALUES
                (1, 'alpha', 10.5),
                (2, 'beta with ''quote''', 0),
                (3, 'gamma', -42.42)",
        );

        $this->dumpPath = sys_get_temp_dir().'/backup-restore-cycle-'.uniqid('', true).'.sql';
    }

    protected function tearDown(): void
    {
        if (is_file($this->dumpPath)) {
            unlink($this->dumpPath);
        }
        parent::tearDown();
    }

    public function testDumpThenRestoreReproducesIdenticalData(): void
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        $application = new Application($kernel);

        $dumpCommand = $application->find('app:backup:dump');
        $dumpExit = (new CommandTester($dumpCommand))->execute([
            '--output' => $this->dumpPath,
        ]);
        self::assertSame(Command::SUCCESS, $dumpExit);
        self::assertFileExists($this->dumpPath);
        self::assertGreaterThan(0, filesize($this->dumpPath), 'Dump file is empty');
        self::assertStringContainsString('backup_restore_cycle_fixture', file_get_contents($this->dumpPath) ?: '');

        // Snapshot the original rows before wiping.
        $expected = $this->connection->fetchAllAssociative(
            'SELECT id, label, amount FROM backup_restore_cycle_fixture ORDER BY id',
        );
        self::assertCount(3, $expected);

        // Mutate state: drop one row, alter another. Restore must undo both changes.
        $this->connection->executeStatement('DELETE FROM backup_restore_cycle_fixture WHERE id = 1');
        $this->connection->executeStatement("UPDATE backup_restore_cycle_fixture SET label = 'tampered' WHERE id = 2");

        $restoreCommand = $application->find('app:backup:restore');
        $restoreTester = new CommandTester($restoreCommand);
        $restoreExit = $restoreTester->execute([
            'file' => $this->dumpPath,
        ]);
        self::assertSame(Command::SUCCESS, $restoreExit, $restoreTester->getDisplay());

        $actual = $this->connection->fetchAllAssociative(
            'SELECT id, label, amount FROM backup_restore_cycle_fixture ORDER BY id',
        );

        self::assertSame($expected, $actual, 'Restored data does not match the dump');
    }
}

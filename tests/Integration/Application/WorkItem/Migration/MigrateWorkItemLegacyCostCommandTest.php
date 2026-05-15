<?php

declare(strict_types=1);

namespace App\Tests\Integration\Application\WorkItem\Migration;

use App\Domain\WorkItem\Migration\HourlyRateProviderInterface;
use App\Entity\Timesheet;
use App\Factory\ContributorFactory;
use App\Factory\ProjectFactory;
use App\Factory\TimesheetFactory;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration tests for `app:workitem:migrate-legacy-cost` (US-113 T-113-05).
 *
 * Couvre :
 *   - dry-run : no DB writes
 *   - execute first run : write legacy_cost_cents + migrated_at
 *   - idempotence : second run skip already-migrated
 *   - drift detection : rate change between runs → flag legacy_cost_drift
 *   - CSV export drifts
 *   - Exit code 0 normal, 1 (FAILURE) si trigger abandon ADR-0013
 */
final class MigrateWorkItemLegacyCostCommandTest extends KernelTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    private CommandTester $tester;
    private string $tmpCsvDir;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();

        $application = new Application(self::$kernel);
        $command = $application->find('app:workitem:migrate-legacy-cost');
        $this->tester = new CommandTester($command);

        $this->tmpCsvDir = sys_get_temp_dir().'/migration-test-'.uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->tmpCsvDir)) {
            return;
        }
        foreach (glob($this->tmpCsvDir.'/*.csv') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmpCsvDir);
    }

    public function testDryRunDoesNotWriteToDatabase(): void
    {
        $this->seedTimesheet(hours: '8.00', cjm: '400.00');

        $this->tester->execute([
            '--dry-run' => true,
            '--csv-report' => '',
        ]);

        static::assertSame(0, $this->tester->getStatusCode());

        $timesheet = $this->fetchSingleTimesheet();
        static::assertNull($timesheet->migratedAt);
        static::assertNull($timesheet->legacyCostCents);
        static::assertFalse($timesheet->legacyCostDrift);
    }

    public function testExecuteWritesSnapshotOnFirstRun(): void
    {
        // 8h × 5000 cents/h = 40000 cents
        $this->seedTimesheet(hours: '8.00', cjm: '400.00');
        $this->overrideHourlyRateProvider(5000);

        $tester = $this->commandTesterWithFreshContainer();
        $tester->execute(['--csv-report' => '']);

        static::assertSame(0, $tester->getStatusCode());

        $timesheet = $this->fetchSingleTimesheet();
        static::assertNotNull($timesheet->migratedAt, 'migrated_at set');
        static::assertSame(40_000, $timesheet->legacyCostCents);
        static::assertFalse($timesheet->legacyCostDrift, 'no drift on first snapshot');
    }

    public function testIdempotentReplaySkipsAlreadyMigrated(): void
    {
        $this->seedTimesheet(hours: '8.00', cjm: '400.00');
        $this->overrideHourlyRateProvider(5000);

        $tester = $this->commandTesterWithFreshContainer();
        $tester->execute(['--csv-report' => '']);
        $firstMigratedAt = $this->fetchSingleTimesheet()->migratedAt;
        static::assertNotNull($firstMigratedAt);

        // Re-run with same tester
        $tester->execute(['--csv-report' => '']);
        $display = $tester->getDisplay();

        static::assertStringContainsString('Already migrated (skip)', $display);
        static::assertSame(0, $tester->getStatusCode());

        // migrated_at unchanged
        $timesheet = $this->fetchSingleTimesheet();
        static::assertEquals($firstMigratedAt, $timesheet->migratedAt);
    }

    public function testDriftDetectedWhenLegacyCostMismatches(): void
    {
        // Seed timesheet legacy = 1 000 000 cents, recomp rate change +0.1 % → 1 000 800
        // Drift 800 cents > threshold 1, mais ratio 0.08 % < 5 % (pas trigger abandon).
        $this->seedTimesheet(hours: '8.00', cjm: '1250.00', legacyCostCents: 1_000_000);
        $this->overrideHourlyRateProvider(125_100); // recomp = 125100 × 8 = 1_000_800

        $tester = $this->commandTesterWithFreshContainer();
        $csvPath = $this->tmpCsvDir.'/drift.csv';
        $tester->execute([
            '--csv-report' => $csvPath,
        ]);

        static::assertSame(0, $tester->getStatusCode(), 'no abandon trigger (drift < 5 %)');

        $timesheet = $this->fetchSingleTimesheet();
        static::assertTrue($timesheet->legacyCostDrift, 'drift flagged');
        // legacy_cost_cents conservé à snapshot original (rollback safety)
        static::assertSame(1_000_000, $timesheet->legacyCostCents);
        static::assertNotNull($timesheet->migratedAt);

        static::assertFileExists($csvPath);
        $csv = (string) file_get_contents($csvPath);
        static::assertStringContainsString('1000000,1000800,800,800', $csv);
    }

    public function testTriggerAbandonCase3ExitsFailure(): void
    {
        // Construire un setup où drift > 5 % total
        // 1 timesheet legacy_cost_cents=100, rate change → recomp=200, delta=100 (100 %)
        $this->seedTimesheet(hours: '1.00', cjm: '8.00', legacyCostCents: 100);
        $this->overrideHourlyRateProvider(200); // recomp = 200 cents

        $application = new Application(self::$kernel);
        $command = $application->find('app:workitem:migrate-legacy-cost');
        $tester = new CommandTester($command);

        $tester->execute(['--csv-report' => '']);

        static::assertSame(1, $tester->getStatusCode(), 'exit FAILURE if abandon trigger');
        static::assertStringContainsString('Trigger abandon ADR-0013 cas 3', $tester->getDisplay());
    }

    public function testCsvAutoPathGeneratedUnderProjectDir(): void
    {
        $this->seedTimesheet(hours: '8.00', cjm: '400.00', legacyCostCents: 40_000);
        $this->overrideHourlyRateProvider(6000);

        $application = new Application(self::$kernel);
        $command = $application->find('app:workitem:migrate-legacy-cost');
        $tester = new CommandTester($command);

        $tester->execute(['--csv-report' => 'auto']);

        $display = $tester->getDisplay();
        static::assertMatchesRegularExpression(
            '#Drift report CSV exporté : .+/var/migration/workitem-cost-drift-\d{4}-\d{2}-\d{2}-\d{6}\.csv#',
            $display,
        );
    }

    private function seedTimesheet(string $hours, string $cjm, ?int $legacyCostCents = null): void
    {
        $contributor = ContributorFactory::createOne([
            'company' => $this->getTestCompany(),
            'cjm' => $cjm,
        ]);

        ProjectFactory::createOne([
            'company' => $this->getTestCompany(),
        ]);

        $timesheet = TimesheetFactory::createOne([
            'company' => $this->getTestCompany(),
            'contributor' => $contributor,
            'date' => new DateTime('2026-04-15'),
            'hours' => $hours,
        ]);

        if ($legacyCostCents !== null) {
            $em = $this->getEntityManager();
            $entity = $em->find(Timesheet::class, $timesheet->id);
            $entity->legacyCostCents = $legacyCostCents;
            $em->flush();
        }
    }

    private function overrideHourlyRateProvider(int $cents): void
    {
        $stub = self::createStub(HourlyRateProviderInterface::class);
        $stub->method('resolveAt')->willReturn($cents);

        self::getContainer()->set(HourlyRateProviderInterface::class, $stub);
    }

    private function fetchSingleTimesheet(): Timesheet
    {
        $em = $this->getEntityManager();
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        self::assertCount(1, $timesheets, 'expected single timesheet seeded');

        return $timesheets[0];
    }

    /**
     * Recrée un CommandTester avec un nouvel Application — utile quand
     * `self::getContainer()->set(...)` override un service après que le
     * CommandTester initial l'a déjà résolu.
     */
    private function commandTesterWithFreshContainer(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('app:workitem:migrate-legacy-cost');

        return new CommandTester($command);
    }
}

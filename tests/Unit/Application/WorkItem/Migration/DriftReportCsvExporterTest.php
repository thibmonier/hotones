<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\WorkItem\Migration;

use App\Application\WorkItem\Migration\DriftReportCsvExporter;
use App\Domain\WorkItem\Migration\MigrationDriftDetail;
use App\Domain\WorkItem\Migration\WorkItemMigrationResult;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DriftReportCsvExporterTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/drift-report-test-'.uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->tmpDir)) {
            return;
        }

        foreach (glob($this->tmpDir.'/*.csv') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tmpDir);
    }

    public function testExportsHeaderAndRowsForEachDrift(): void
    {
        $result = new WorkItemMigrationResult(
            migrated: 2,
            alreadyMigrated: 0,
            missingRate: 0,
            drifts: [
                new MigrationDriftDetail(timesheetId: 10, contributorId: 1, legacyCostCents: 40_000, recomputedCostCents: 48_000),
                new MigrationDriftDetail(timesheetId: 11, contributorId: 2, legacyCostCents: 5000, recomputedCostCents: 4500),
            ],
            totalLegacyCostCents: 45_000,
            totalDriftCents: 8500,
        );

        $path = $this->tmpDir.'/drift.csv';

        $exporter = new DriftReportCsvExporter();
        $returnedPath = $exporter->export($result, $path);

        static::assertSame($path, $returnedPath);
        static::assertFileExists($path);

        $content = file_get_contents($path);
        static::assertStringContainsString('timesheet_id,contributor_id,legacy_cost_cents,recomputed_cost_cents,delta_cents,abs_delta_cents', (string) $content);
        static::assertStringContainsString('10,1,40000,48000,8000,8000', (string) $content);
        static::assertStringContainsString('11,2,5000,4500,-500,500', (string) $content);
    }

    public function testExportsEmptyCsvWithHeaderOnlyWhenNoDrifts(): void
    {
        $result = new WorkItemMigrationResult(
            migrated: 0,
            alreadyMigrated: 0,
            missingRate: 0,
            drifts: [],
            totalLegacyCostCents: 0,
            totalDriftCents: 0,
        );

        $path = $this->tmpDir.'/empty.csv';
        (new DriftReportCsvExporter())->export($result, $path);

        $lines = file($path);
        static::assertCount(1, $lines, 'header only');
        static::assertStringContainsString('timesheet_id', $lines[0]);
    }

    public function testCreatesDirectoryIfMissing(): void
    {
        $nested = $this->tmpDir.'/sub/dir';
        $path = $nested.'/drift.csv';

        $exporter = new DriftReportCsvExporter();
        $exporter->export(
            new WorkItemMigrationResult(0, 0, 0, [], 0, 0),
            $path,
        );

        static::assertDirectoryExists($nested);
        static::assertFileExists($path);

        // Cleanup nested dirs
        @unlink($path);
        @rmdir($nested);
        @rmdir(dirname($nested));
    }

    public function testDefaultPathFollowsConvention(): void
    {
        $now = new DateTimeImmutable('2026-05-13T14:30:45+00:00');
        $path = DriftReportCsvExporter::defaultPath('/var/www/html', $now);

        static::assertSame('/var/www/html/var/migration/workitem-cost-drift-2026-05-13-143045.csv', $path);
    }
}

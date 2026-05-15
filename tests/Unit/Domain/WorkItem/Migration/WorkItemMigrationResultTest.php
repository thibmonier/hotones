<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\Migration;

use App\Domain\WorkItem\Migration\MigrationDriftDetail;
use App\Domain\WorkItem\Migration\WorkItemMigrationResult;
use PHPUnit\Framework\TestCase;

final class WorkItemMigrationResultTest extends TestCase
{
    public function testTotalProcessedSumsMigratedAlreadyMigratedAndMissingRate(): void
    {
        $result = new WorkItemMigrationResult(
            migrated: 100,
            alreadyMigrated: 50,
            missingRate: 5,
            drifts: [],
            totalLegacyCostCents: 0,
            totalDriftCents: 0,
        );

        self::assertSame(155, $result->totalProcessed());
    }

    public function testDriftCountReturnsArraySize(): void
    {
        $result = new WorkItemMigrationResult(
            migrated: 0,
            alreadyMigrated: 0,
            missingRate: 0,
            drifts: [
                new MigrationDriftDetail(1, 1, 1000, 1100),
                new MigrationDriftDetail(2, 1, 2000, 2050),
                new MigrationDriftDetail(3, 1, 500, 480),
            ],
            totalLegacyCostCents: 3500,
            totalDriftCents: 170,
        );

        self::assertSame(3, $result->driftCount());
    }

    public function testDriftRatioReturnsZeroWhenTotalLegacyCostIsZero(): void
    {
        $result = new WorkItemMigrationResult(0, 0, 0, [], 0, 0);

        self::assertSame(0.0, $result->driftRatio());
    }

    public function testDriftRatioCalculatesCorrectly(): void
    {
        $result = new WorkItemMigrationResult(
            migrated: 100,
            alreadyMigrated: 0,
            missingRate: 0,
            drifts: [],
            totalLegacyCostCents: 100_000,
            totalDriftCents: 3_000,
        );

        // 3000 / 100000 = 0.03 (3%)
        self::assertEqualsWithDelta(0.03, $result->driftRatio(), 0.001);
    }

    public function testShouldNotTriggerAbandonCase3WhenDriftBelow5Percent(): void
    {
        $result = new WorkItemMigrationResult(
            migrated: 0,
            alreadyMigrated: 0,
            missingRate: 0,
            drifts: [],
            totalLegacyCostCents: 100_000,
            totalDriftCents: 4_000, // 4 %
        );

        self::assertFalse($result->shouldTriggerAbandonCase3());
    }

    public function testShouldTriggerAbandonCase3WhenDriftAbove5Percent(): void
    {
        $result = new WorkItemMigrationResult(
            migrated: 0,
            alreadyMigrated: 0,
            missingRate: 0,
            drifts: [],
            totalLegacyCostCents: 100_000,
            totalDriftCents: 6_000, // 6 %
        );

        self::assertTrue($result->shouldTriggerAbandonCase3());
    }

    public function testShouldNotTriggerAbandonCase3AtExactly5Percent(): void
    {
        $result = new WorkItemMigrationResult(
            migrated: 0,
            alreadyMigrated: 0,
            missingRate: 0,
            drifts: [],
            totalLegacyCostCents: 100_000,
            totalDriftCents: 5_000, // exactement 5 %
        );

        // Strict > 0.05 → exactement 5% ne trigger pas
        self::assertFalse($result->shouldTriggerAbandonCase3());
    }
}

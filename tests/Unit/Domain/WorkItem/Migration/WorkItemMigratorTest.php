<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\Migration;

use App\Domain\WorkItem\Migration\HourlyRateProviderInterface;
use App\Domain\WorkItem\Migration\LegacyTimesheetRecord;
use App\Domain\WorkItem\Migration\WorkItemMigrator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class WorkItemMigratorTest extends TestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-05-13T09:00:00+00:00');
    }

    public function testEmptyRecordsProducesEmptyResult(): void
    {
        $migrator = new WorkItemMigrator($this->rateProvider(5000));

        $result = $migrator->migrate([], $this->now);

        self::assertSame(0, $result->migrated);
        self::assertSame(0, $result->alreadyMigrated);
        self::assertSame(0, $result->missingRate);
        self::assertSame(0, $result->driftCount());
        self::assertSame(0.0, $result->driftRatio());
    }

    public function testFirstSnapshotWritesBaselineWithoutDrift(): void
    {
        $records = [
            $this->record(timesheetId: 1, hours: 8.0, legacyCostCents: null, migratedAt: null),
            $this->record(timesheetId: 2, hours: 4.0, legacyCostCents: null, migratedAt: null),
        ];

        $migrator = new WorkItemMigrator($this->rateProvider(5000)); // 50 €/h

        $result = $migrator->migrate($records, $this->now);

        self::assertSame(2, $result->migrated);
        self::assertSame(0, $result->driftCount());
        // 8h × 5000 + 4h × 5000 = 60000
        self::assertSame(60000, $result->totalLegacyCostCents);
    }

    public function testSecondRunWithSameRateDoesNotProduceDrift(): void
    {
        // Items déjà snapshottés avec rate 50 €/h × 8h = 40000 cents
        $records = [
            $this->record(timesheetId: 1, hours: 8.0, legacyCostCents: 40000, migratedAt: null),
        ];

        $migrator = new WorkItemMigrator($this->rateProvider(5000));

        $result = $migrator->migrate($records, $this->now);

        self::assertSame(0, $result->driftCount());
        self::assertSame(40000, $result->totalLegacyCostCents);
        self::assertSame(0, $result->totalDriftCents);
    }

    public function testRateChangeProducesDrift(): void
    {
        // Snapshot legacy à 40000 cents, mais rate ahora a changé à 6000 (60 €/h)
        // Recomputed = 6000 × 8 = 48000, delta = 8000 cents > 1
        $records = [
            $this->record(timesheetId: 1, hours: 8.0, legacyCostCents: 40000, migratedAt: null),
        ];

        $migrator = new WorkItemMigrator($this->rateProvider(6000));

        $result = $migrator->migrate($records, $this->now);

        self::assertSame(1, $result->driftCount());
        self::assertSame(1, $result->migrated);
        self::assertSame(8000, $result->totalDriftCents);
        self::assertSame(40000, $result->totalLegacyCostCents);
        self::assertSame(1, $result->drifts[0]->timesheetId);
        self::assertSame(40000, $result->drifts[0]->legacyCostCents);
        self::assertSame(48000, $result->drifts[0]->recomputedCostCents);
        self::assertSame(8000, $result->drifts[0]->deltaCents());
    }

    public function testDriftBelowOrAtThresholdIsIgnored(): void
    {
        // delta = 1 cent exactly → pas de drift (threshold = > 1, pas >=)
        $records = [
            $this->record(timesheetId: 1, hours: 1.0, legacyCostCents: 5000, migratedAt: null),
        ];

        // Rate 5001 → recomputed 5001 → delta 1
        $migrator = new WorkItemMigrator($this->rateProvider(5001));

        $result = $migrator->migrate($records, $this->now);

        self::assertSame(0, $result->driftCount(), 'delta 1 cent ne déclenche pas drift');
    }

    public function testAlreadyMigratedRecordsSkipped(): void
    {
        $migratedAt = $this->now->modify('-1 day');
        $records = [
            $this->record(timesheetId: 1, hours: 8.0, legacyCostCents: 40000, migratedAt: $migratedAt),
        ];

        $migrator = new WorkItemMigrator($this->rateProvider(99999)); // rate qui produirait drift

        $result = $migrator->migrate($records, $this->now);

        self::assertSame(0, $result->migrated);
        self::assertSame(1, $result->alreadyMigrated);
        self::assertSame(0, $result->driftCount(), 'idempotence : pas de re-process');
        self::assertSame(40000, $result->totalLegacyCostCents);
    }

    public function testMissingRateRecordsTracked(): void
    {
        $records = [
            $this->record(timesheetId: 1, hours: 8.0, legacyCostCents: null, migratedAt: null),
            $this->record(timesheetId: 2, hours: 4.0, legacyCostCents: null, migratedAt: null),
        ];

        $migrator = new WorkItemMigrator($this->nullRateProvider());

        $result = $migrator->migrate($records, $this->now);

        self::assertSame(2, $result->missingRate);
        self::assertSame(0, $result->migrated);
        self::assertSame(0, $result->driftCount());
    }

    public function testTriggerAbandonCase3WhenDriftRatioExceeds5Percent(): void
    {
        // Total legacy = 100000, drift = 6000 → 6 % > 5 % → trigger
        $records = [
            $this->record(timesheetId: 1, hours: 10.0, legacyCostCents: 100000, migratedAt: null),
        ];
        $migrator = new WorkItemMigrator($this->rateProvider(10600)); // recomp = 106000, delta = 6000

        $result = $migrator->migrate($records, $this->now);

        self::assertTrue($result->shouldTriggerAbandonCase3());
        self::assertEqualsWithDelta(0.06, $result->driftRatio(), 0.001);
    }

    public function testNoAbandonTriggerWhenDriftBelow5Percent(): void
    {
        $records = [
            $this->record(timesheetId: 1, hours: 10.0, legacyCostCents: 100000, migratedAt: null),
        ];
        $migrator = new WorkItemMigrator($this->rateProvider(10200)); // delta = 2000 = 2 %

        $result = $migrator->migrate($records, $this->now);

        self::assertFalse($result->shouldTriggerAbandonCase3());
    }

    public function testMixedBatchProducesAccurateAggregation(): void
    {
        $records = [
            // 1 firstSnapshot (no drift)
            $this->record(1, 8.0, null, null),
            // 1 alreadyMigrated (skipped)
            $this->record(2, 4.0, 20000, $this->now->modify('-1 day')),
            // 1 drift
            $this->record(3, 5.0, 24000, null),
            // 1 missingRate via fallback
        ];

        // Rate provider returns 5000 cents (50 €/h) for IDs 1,3 ; null for 4
        $rateProvider = new class implements HourlyRateProviderInterface {
            public function resolveAt(int $contributorId, DateTimeImmutable $workDate): ?int
            {
                return $contributorId === 99 ? null : 5000;
            }
        };

        $migrator = new WorkItemMigrator($rateProvider);

        $result = $migrator->migrate($records, $this->now);

        self::assertSame(2, $result->migrated); // ts 1 + 3
        self::assertSame(1, $result->alreadyMigrated); // ts 2
        self::assertSame(0, $result->missingRate);
        self::assertSame(1, $result->driftCount()); // ts 3 : recomp 25000 - 24000 = 1000
    }

    private function record(
        int $timesheetId,
        float $hours,
        ?int $legacyCostCents,
        ?DateTimeImmutable $migratedAt,
    ): LegacyTimesheetRecord {
        return new LegacyTimesheetRecord(
            timesheetId: $timesheetId,
            contributorId: 1,
            workDate: $this->now->modify('-30 days'),
            hours: $hours,
            legacyCostCents: $legacyCostCents,
            migratedAt: $migratedAt,
        );
    }

    private function rateProvider(int $cents): HourlyRateProviderInterface
    {
        $stub = self::createStub(HourlyRateProviderInterface::class);
        $stub->method('resolveAt')->willReturn($cents);

        return $stub;
    }

    private function nullRateProvider(): HourlyRateProviderInterface
    {
        return new class implements HourlyRateProviderInterface {
            public function resolveAt(int $contributorId, DateTimeImmutable $workDate): ?int
            {
                return null;
            }
        };
    }
}

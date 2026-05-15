<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Service;

use App\Domain\Project\Service\MarginAdoptionCalculator;
use App\Domain\Project\Service\ProjectMarginSnapshotRecord;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class MarginAdoptionCalculatorTest extends TestCase
{
    private MarginAdoptionCalculator $calculator;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->calculator = new MarginAdoptionCalculator();
        $this->now = new DateTimeImmutable('2026-05-12T12:00:00+00:00');
    }

    public function testReturnsEmptyStatsForNoRecords(): void
    {
        $stats = $this->calculator->classify([], $this->now);

        static::assertSame(0, $stats->totalActive);
        static::assertSame(0, $stats->freshCount);
        static::assertSame(0, $stats->staleWarningCount);
        static::assertSame(0, $stats->staleCriticalCount);
        static::assertSame(0.0, $stats->freshPercent);
    }

    public function testClassifiesRecordsByAge(): void
    {
        $records = [
            // Fresh : 2 j
            $this->record(1, 'A', daysAgo: 2),
            $this->record(2, 'B', daysAgo: 5),
            // Stale warning : 7-30 j
            $this->record(3, 'C', daysAgo: 10),
            $this->record(4, 'D', daysAgo: 25),
            // Stale critical : ≥ 30 j
            $this->record(5, 'E', daysAgo: 40),
            // Stale critical : null
            $this->record(6, 'F', daysAgo: null),
        ];

        $stats = $this->calculator->classify($records, $this->now);

        static::assertSame(6, $stats->totalActive);
        static::assertSame(2, $stats->freshCount);
        static::assertSame(2, $stats->staleWarningCount);
        static::assertSame(2, $stats->staleCriticalCount);
        static::assertEqualsWithDelta(33.3, $stats->freshPercent, 0.1);
    }

    public function testBoundary7DaysGoesToStaleWarning(): void
    {
        $records = [$this->record(1, 'A', daysAgo: 7)];

        $stats = $this->calculator->classify($records, $this->now);

        static::assertSame(0, $stats->freshCount);
        static::assertSame(1, $stats->staleWarningCount);
    }

    public function testBoundary30DaysGoesToStaleCritical(): void
    {
        $records = [$this->record(1, 'A', daysAgo: 30)];

        $stats = $this->calculator->classify($records, $this->now);

        static::assertSame(0, $stats->staleWarningCount);
        static::assertSame(1, $stats->staleCriticalCount);
    }

    public function testNullMarginCalculatedAtAlwaysStaleCritical(): void
    {
        $records = [
            $this->record(1, 'NeverCalculated1', daysAgo: null),
            $this->record(2, 'NeverCalculated2', daysAgo: null),
        ];

        $stats = $this->calculator->classify($records, $this->now);

        static::assertSame(2, $stats->staleCriticalCount);
        static::assertSame(0.0, $stats->freshPercent);
    }

    public function testFreshPercentComputedCorrectly(): void
    {
        $records = [
            $this->record(1, 'A', daysAgo: 1),
            $this->record(2, 'B', daysAgo: 1),
            $this->record(3, 'C', daysAgo: 1),
            $this->record(4, 'D', daysAgo: 50),
        ];

        $stats = $this->calculator->classify($records, $this->now);

        static::assertSame(3, $stats->freshCount);
        static::assertSame(75.0, $stats->freshPercent);
    }

    private function record(int $projectId, string $name, ?int $daysAgo): ProjectMarginSnapshotRecord
    {
        $marginAt = $daysAgo === null
            ? null
            : $this->now->modify(sprintf('-%d days', $daysAgo));

        return new ProjectMarginSnapshotRecord(
            projectId: $projectId,
            projectName: $name,
            marginCalculatedAt: $marginAt,
        );
    }
}

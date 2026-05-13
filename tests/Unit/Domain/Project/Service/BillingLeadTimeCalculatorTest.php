<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Service;

use App\Domain\Project\Service\BillingLeadTimeCalculator;
use App\Domain\Project\Service\QuoteInvoiceRecord;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BillingLeadTimeCalculatorTest extends TestCase
{
    private BillingLeadTimeCalculator $calculator;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->calculator = new BillingLeadTimeCalculator();
        $this->now = new DateTimeImmutable('2026-05-12T00:00:00+00:00');
    }

    public function testReturnsEmptyStatsWhenNoRecords(): void
    {
        $stats = $this->calculator->calculateRolling([], 30, $this->now);

        self::assertSame(0, $stats->count);
        self::assertSame(0.0, $stats->p50->getDays());
        self::assertSame(0.0, $stats->p75->getDays());
        self::assertSame(0.0, $stats->p95->getDays());
    }

    public function testComputesMedianForSingleRecord(): void
    {
        $records = [$this->makeRecord(daysAgoEmitted: 5, leadTimeDays: 10)];

        $stats = $this->calculator->calculateRolling($records, 30, $this->now);

        self::assertSame(1, $stats->count);
        self::assertEqualsWithDelta(10.0, $stats->p50->getDays(), 0.1);
        self::assertEqualsWithDelta(10.0, $stats->p95->getDays(), 0.1);
    }

    public function testComputesMedianForOddCount(): void
    {
        $records = [
            $this->makeRecord(daysAgoEmitted: 5, leadTimeDays: 10),
            $this->makeRecord(daysAgoEmitted: 5, leadTimeDays: 20),
            $this->makeRecord(daysAgoEmitted: 5, leadTimeDays: 30),
        ];

        $stats = $this->calculator->calculateRolling($records, 30, $this->now);

        self::assertSame(3, $stats->count);
        self::assertEqualsWithDelta(20.0, $stats->p50->getDays(), 0.1);
    }

    public function testComputesP75AndP95WithInterpolation(): void
    {
        // 100 records, lead times 1..100 → p50=50.5, p75=75.25, p95=95.05
        $records = [];
        for ($i = 1; $i <= 100; ++$i) {
            $records[] = $this->makeRecord(daysAgoEmitted: 5, leadTimeDays: $i);
        }

        $stats = $this->calculator->calculateRolling($records, 30, $this->now);

        self::assertSame(100, $stats->count);
        self::assertEqualsWithDelta(50.5, $stats->p50->getDays(), 0.5);
        self::assertEqualsWithDelta(75.25, $stats->p75->getDays(), 0.5);
        self::assertEqualsWithDelta(95.05, $stats->p95->getDays(), 0.5);
    }

    public function testExcludesRecordsOutsideWindow(): void
    {
        $records = [
            $this->makeRecord(daysAgoEmitted: 10, leadTimeDays: 5),
            $this->makeRecord(daysAgoEmitted: 60, leadTimeDays: 50), // hors 30j window
        ];

        $stats = $this->calculator->calculateRolling($records, 30, $this->now);

        self::assertSame(1, $stats->count);
        self::assertEqualsWithDelta(5.0, $stats->p50->getDays(), 0.1);
    }

    public function testRejectsNegativeLeadTimeRecord(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new QuoteInvoiceRecord(
            signedAt: new DateTimeImmutable('2026-05-10'),
            emittedAt: new DateTimeImmutable('2026-05-05'),
        );
    }

    public function testHandlesWindowDays365(): void
    {
        $records = [
            $this->makeRecord(daysAgoEmitted: 200, leadTimeDays: 15),
            $this->makeRecord(daysAgoEmitted: 300, leadTimeDays: 25),
        ];

        $stats = $this->calculator->calculateRolling($records, 365, $this->now);

        self::assertSame(2, $stats->count);
        // Interpolation p50 for [15, 25] at index 0.5 → 20
        self::assertEqualsWithDelta(20.0, $stats->p50->getDays(), 0.1);
    }

    private function makeRecord(int $daysAgoEmitted, int $leadTimeDays): QuoteInvoiceRecord
    {
        $emittedAt = $this->now->modify(sprintf('-%d days', $daysAgoEmitted));
        $signedAt = $emittedAt->modify(sprintf('-%d days', $leadTimeDays));

        return new QuoteInvoiceRecord(
            signedAt: $signedAt,
            emittedAt: $emittedAt,
            clientId: 1,
            clientName: 'Test Client',
        );
    }
}

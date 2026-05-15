<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Service;

use App\Domain\Project\Service\BillingLeadTimeStats;
use App\Domain\Project\ValueObject\LeadTimeDays;
use PHPUnit\Framework\TestCase;

final class BillingLeadTimeStatsTest extends TestCase
{
    public function testEmptyFactoryReturnsZeroedStats(): void
    {
        $stats = BillingLeadTimeStats::empty();

        self::assertSame(0.0, $stats->p50->getDays());
        self::assertSame(0.0, $stats->p75->getDays());
        self::assertSame(0.0, $stats->p95->getDays());
        self::assertSame(0, $stats->count);
    }

    public function testConstructorAssignsAllPercentilesAndCount(): void
    {
        $stats = new BillingLeadTimeStats(
            p50: LeadTimeDays::fromDays(15.0),
            p75: LeadTimeDays::fromDays(28.0),
            p95: LeadTimeDays::fromDays(60.0),
            count: 42,
        );

        self::assertSame(15.0, $stats->p50->getDays());
        self::assertSame(28.0, $stats->p75->getDays());
        self::assertSame(60.0, $stats->p95->getDays());
        self::assertSame(42, $stats->count);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Service;

use App\Domain\Project\Service\MarginAdoptionStats;
use PHPUnit\Framework\TestCase;

final class MarginAdoptionStatsTest extends TestCase
{
    public function testEmptyFactoryReturnsZeroedStats(): void
    {
        $stats = MarginAdoptionStats::empty();

        self::assertSame(0, $stats->freshCount);
        self::assertSame(0, $stats->staleWarningCount);
        self::assertSame(0, $stats->staleCriticalCount);
        self::assertSame(0, $stats->totalActive);
        self::assertSame(0.0, $stats->freshPercent);
    }

    public function testConstructorAssignsAllFields(): void
    {
        $stats = new MarginAdoptionStats(
            freshCount: 10,
            staleWarningCount: 3,
            staleCriticalCount: 2,
            totalActive: 15,
            freshPercent: 66.67,
        );

        self::assertSame(10, $stats->freshCount);
        self::assertSame(3, $stats->staleWarningCount);
        self::assertSame(2, $stats->staleCriticalCount);
        self::assertSame(15, $stats->totalActive);
        self::assertSame(66.67, $stats->freshPercent);
    }
}

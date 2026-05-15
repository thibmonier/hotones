<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\Migration;

use App\Domain\WorkItem\Migration\MigrationDriftDetail;
use PHPUnit\Framework\TestCase;

final class MigrationDriftDetailTest extends TestCase
{
    public function testDeltaCentsPositiveWhenRecomputedGreaterThanLegacy(): void
    {
        $drift = new MigrationDriftDetail(
            timesheetId: 1,
            contributorId: 42,
            legacyCostCents: 40_000,
            recomputedCostCents: 48_000,
        );

        self::assertSame(8_000, $drift->deltaCents());
        self::assertSame(8_000, $drift->absoluteDeltaCents());
    }

    public function testDeltaCentsNegativeWhenRecomputedLessThanLegacy(): void
    {
        $drift = new MigrationDriftDetail(
            timesheetId: 2,
            contributorId: 42,
            legacyCostCents: 5_000,
            recomputedCostCents: 4_500,
        );

        self::assertSame(-500, $drift->deltaCents());
        self::assertSame(500, $drift->absoluteDeltaCents());
    }

    public function testDeltaCentsZeroWhenAmountsMatch(): void
    {
        $drift = new MigrationDriftDetail(
            timesheetId: 3,
            contributorId: 42,
            legacyCostCents: 1_000,
            recomputedCostCents: 1_000,
        );

        self::assertSame(0, $drift->deltaCents());
        self::assertSame(0, $drift->absoluteDeltaCents());
    }
}

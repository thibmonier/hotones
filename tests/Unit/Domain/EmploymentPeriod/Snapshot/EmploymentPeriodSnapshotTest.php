<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\EmploymentPeriod\Snapshot;

use App\Domain\EmploymentPeriod\Snapshot\EmploymentPeriodSnapshot;
use App\Domain\EmploymentPeriod\ValueObject\WeeklyHours;
use App\Domain\EmploymentPeriod\ValueObject\WorkTimePercentage;
use PHPUnit\Framework\TestCase;

final class EmploymentPeriodSnapshotTest extends TestCase
{
    public function testDailyMaxHoursStandardFullTime(): void
    {
        // 35h × 100% / 5 = 7.0h
        $snapshot = new EmploymentPeriodSnapshot(
            weeklyHours: WeeklyHours::fromFloat(35.0),
            workTimePercentage: WorkTimePercentage::fromFloat(100.0),
        );

        self::assertSame(7.0, $snapshot->dailyMaxHours()->getValue());
    }

    public function testDailyMaxHours80PercentPartTime(): void
    {
        // 35h × 80% / 5 = 5.6h
        $snapshot = new EmploymentPeriodSnapshot(
            weeklyHours: WeeklyHours::fromFloat(35.0),
            workTimePercentage: WorkTimePercentage::fromFloat(80.0),
        );

        self::assertSame(5.6, $snapshot->dailyMaxHours()->getValue());
    }

    public function testDailyMaxHours50PercentPartTime(): void
    {
        // 40h × 50% / 5 = 4.0h
        $snapshot = new EmploymentPeriodSnapshot(
            weeklyHours: WeeklyHours::fromFloat(40.0),
            workTimePercentage: WorkTimePercentage::fromFloat(50.0),
        );

        self::assertSame(4.0, $snapshot->dailyMaxHours()->getValue());
    }
}

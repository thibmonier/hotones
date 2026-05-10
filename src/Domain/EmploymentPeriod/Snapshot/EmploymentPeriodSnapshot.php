<?php

declare(strict_types=1);

namespace App\Domain\EmploymentPeriod\Snapshot;

use App\Domain\EmploymentPeriod\ValueObject\WeeklyHours;
use App\Domain\EmploymentPeriod\ValueObject\WorkTimePercentage;
use App\Domain\WorkItem\ValueObject\WorkedHours;

/**
 * Read-only snapshot of an EmploymentPeriod active for a given (contributor, date).
 *
 * Domain DTO emitted by EmploymentPeriodRepositoryInterface implementations
 * (ACL adapter wrapping legacy flat repo, AT-3.1 ADR-0016).
 */
final readonly class EmploymentPeriodSnapshot
{
    private const int WORKING_DAYS_PER_WEEK = 5;

    public function __construct(
        public WeeklyHours $weeklyHours,
        public WorkTimePercentage $workTimePercentage,
    ) {
    }

    /**
     * Daily max hours = (weeklyHours × workTimePercentage / 100) / 5.
     *
     * Example: 35h × 100% / 5 = 7.0h
     * Example: 35h × 80% / 5 = 5.6h
     */
    public function dailyMaxHours(): WorkedHours
    {
        $value = ($this->weeklyHours->getValue() * $this->workTimePercentage->asRatio()) / self::WORKING_DAYS_PER_WEEK;

        return WorkedHours::fromFloat($value);
    }
}

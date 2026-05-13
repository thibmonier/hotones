<?php

declare(strict_types=1);

namespace App\Application\Project\Query\DsoKpi;

use App\Domain\Project\Repository\DsoReadModelRepositoryInterface;
use App\Domain\Project\Service\DsoCalculator;
use DateTimeImmutable;

/**
 * Compute DSO KPI widget data for the business dashboard (US-110 T-110-04).
 *
 * Aggregates 3 rolling windows (30/90/365 days) + 30-day trend vs previous
 * period + warning flag against configurable threshold.
 *
 * Default warning threshold = 45 days (US-110 AC). Hierarchical override
 * (global → Client) is the scope of US-108 pattern, planned for T-110-05
 * Slack alerting integration.
 */
final readonly class ComputeDsoKpiHandler
{
    private const float DEFAULT_WARNING_THRESHOLD_DAYS = 45.0;
    private const float TREND_STABILITY_DELTA_DAYS = 1.0;

    public function __construct(
        private DsoReadModelRepositoryInterface $repository,
        private DsoCalculator $calculator,
    ) {
    }

    public function __invoke(?DateTimeImmutable $now = null): DsoKpiDto
    {
        $now = $now ?? new DateTimeImmutable();

        $dso30 = $this->computeWindow(30, $now);
        $dso90 = $this->computeWindow(90, $now);
        $dso365 = $this->computeWindow(365, $now);

        $previousPeriodEnd = $now->modify('-30 days');
        $previousDso30 = $this->computeWindow(30, $previousPeriodEnd);

        return new DsoKpiDto(
            dso30Days: $dso30,
            dso90Days: $dso90,
            dso365Days: $dso365,
            trend30: $this->trend($dso30, $previousDso30),
            warningThresholdDays: self::DEFAULT_WARNING_THRESHOLD_DAYS,
            warningTriggered: $dso30 > self::DEFAULT_WARNING_THRESHOLD_DAYS,
        );
    }

    private function computeWindow(int $windowDays, DateTimeImmutable $now): float
    {
        $records = $this->repository->findPaidInRollingWindow($windowDays, $now);

        return $this->calculator
            ->calculateRolling($records, $windowDays, $now)
            ->getDays();
    }

    private function trend(float $current, float $previous): DsoTrend
    {
        $delta = $current - $previous;

        if (abs($delta) < self::TREND_STABILITY_DELTA_DAYS) {
            return DsoTrend::Stable;
        }

        return $delta > 0 ? DsoTrend::Up : DsoTrend::Down;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Project\Query\MarginAdoptionKpi;

use App\Domain\Project\Repository\MarginAdoptionReadModelRepositoryInterface;
use App\Domain\Project\Service\MarginAdoptionCalculator;
use DateTimeImmutable;

/**
 * Compute margin adoption KPI widget data for the business dashboard
 * (US-112 T-112-03).
 *
 * Pattern aligné US-110/US-111 ComputeKpiHandler.
 *
 * Warning threshold default 60 % (US-112 AC). Si freshPercent < 60 %,
 * widget marqué warning (orange). Hierarchical override scope T-112-04
 * Slack alerting.
 */
final readonly class ComputeMarginAdoptionKpiHandler
{
    public const float DEFAULT_WARNING_THRESHOLD_PERCENT = 60.0;

    public function __construct(
        private MarginAdoptionReadModelRepositoryInterface $repository,
        private MarginAdoptionCalculator $calculator,
    ) {
    }

    public function __invoke(?DateTimeImmutable $now = null): MarginAdoptionKpiDto
    {
        $now = $now ?? new DateTimeImmutable();

        $records = $this->repository->findActiveWithMarginSnapshot();
        $stats = $this->calculator->classify($records, $now);

        return new MarginAdoptionKpiDto(
            stats: $stats,
            warningThresholdPercent: self::DEFAULT_WARNING_THRESHOLD_PERCENT,
            warningTriggered: $stats->totalActive > 0
                && $stats->freshPercent < self::DEFAULT_WARNING_THRESHOLD_PERCENT,
        );
    }
}

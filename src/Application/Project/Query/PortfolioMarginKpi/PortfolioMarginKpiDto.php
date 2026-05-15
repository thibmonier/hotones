<?php

declare(strict_types=1);

namespace App\Application\Project\Query\PortfolioMarginKpi;

/**
 * DTO read-only du KPI Marge moyenne portefeuille (US-117 T-117-04).
 */
final readonly class PortfolioMarginKpiDto
{
    public function __construct(
        public float $averagePercent,
        public int $projectsWithSnapshot,
        public int $projectsWithoutSnapshot,
        public int $projectsAboveTarget,
        public int $projectsBelowTarget,
        public float $targetMarginPercent,
        public float $warningThresholdPercent,
        public bool $warningTriggered,
    ) {
    }

    public function totalActiveProjects(): int
    {
        return $this->projectsWithSnapshot + $this->projectsWithoutSnapshot;
    }
}

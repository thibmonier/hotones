<?php

declare(strict_types=1);

namespace App\Application\Project\Query\PortfolioMarginKpi;

use App\Domain\Project\Repository\PortfolioMarginReadModelRepositoryInterface;
use App\Domain\Project\Service\PortfolioMarginCalculator;
use DateTimeImmutable;

/**
 * Handler CQRS read-only du KPI Marge moyenne portefeuille (US-117 T-117-04).
 *
 * Target marge défaut 20 % (séparation projets above/below). Warning orange
 * si marge moyenne pondérée < 15 %. Configurable hiérarchique pattern US-108
 * — TODO sprint-027.
 */
final readonly class ComputePortfolioMarginKpiHandler
{
    private const float DEFAULT_TARGET_MARGIN_PERCENT = 20.0;
    private const float DEFAULT_WARNING_THRESHOLD_PERCENT = 15.0;

    public function __construct(
        private PortfolioMarginReadModelRepositoryInterface $repository,
        private PortfolioMarginCalculator $calculator,
    ) {
    }

    public function __invoke(?DateTimeImmutable $now = null): PortfolioMarginKpiDto
    {
        $now ??= new DateTimeImmutable();

        $records = $this->repository->findActiveProjectsWithSnapshot($now);
        $margin = $this->calculator->calculate($records, $now, self::DEFAULT_TARGET_MARGIN_PERCENT);

        return new PortfolioMarginKpiDto(
            averagePercent: $margin->getAveragePercent(),
            projectsWithSnapshot: $margin->getProjectsWithSnapshot(),
            projectsWithoutSnapshot: $margin->getProjectsWithoutSnapshot(),
            projectsAboveTarget: $margin->getProjectsAboveTarget(),
            projectsBelowTarget: $margin->getProjectsBelowTarget(),
            targetMarginPercent: self::DEFAULT_TARGET_MARGIN_PERCENT,
            warningThresholdPercent: self::DEFAULT_WARNING_THRESHOLD_PERCENT,
            warningTriggered: $margin->getProjectsWithSnapshot() > 0
                && $margin->getAveragePercent() < self::DEFAULT_WARNING_THRESHOLD_PERCENT,
        );
    }
}

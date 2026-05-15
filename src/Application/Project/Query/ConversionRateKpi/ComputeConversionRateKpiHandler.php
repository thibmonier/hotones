<?php

declare(strict_types=1);

namespace App\Application\Project\Query\ConversionRateKpi;

use App\Domain\Project\Repository\ConversionRateReadModelRepositoryInterface;
use App\Domain\Project\Service\ConversionRateCalculator;
use DateTimeImmutable;

/**
 * Handler CQRS read-only du KPI Taux de conversion (US-115 T-115-04).
 *
 * Seuil warning défaut : 40 % (rate30 < seuil → orange).
 * Configurable hiérarchique pattern US-108 — TODO sprint-026.
 */
final readonly class ComputeConversionRateKpiHandler
{
    private const float DEFAULT_WARNING_THRESHOLD_PERCENT = 40.0;

    public function __construct(
        private ConversionRateReadModelRepositoryInterface $repository,
        private ConversionRateCalculator $calculator,
    ) {
    }

    public function __invoke(?DateTimeImmutable $now = null): ConversionRateKpiDto
    {
        $now ??= new DateTimeImmutable();

        $records = $this->repository->findConversionRecords($now);
        $rate = $this->calculator->calculate($records, $now);

        return new ConversionRateKpiDto(
            rate30Percent: $rate->getRate30Percent(),
            rate90Percent: $rate->getRate90Percent(),
            rate365Percent: $rate->getRate365Percent(),
            emitted30Count: $rate->getEmitted30Count(),
            converted30Count: $rate->getConverted30Count(),
            trend30: $rate->getTrend30(),
            warningThresholdPercent: self::DEFAULT_WARNING_THRESHOLD_PERCENT,
            warningTriggered: $rate->getRate30Percent() < self::DEFAULT_WARNING_THRESHOLD_PERCENT
                && $rate->getEmitted30Count() > 0,
        );
    }
}

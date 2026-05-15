<?php

declare(strict_types=1);

namespace App\Application\Project\Query\RevenueForecastKpi;

use App\Domain\Project\Repository\RevenueForecastReadModelRepositoryInterface;
use App\Domain\Project\Service\RevenueForecastCalculator;
use DateTimeImmutable;

/**
 * Handler CQRS read-only du KPI Revenue forecast (US-114 T-114-04).
 *
 * - Coefficient probabilité devis : défaut 0.3 (configurable hiérarchique
 *   pattern US-108 — TODO sprint-026 si besoin override par société)
 * - Seuil warning : montant plancher trésorerie (défaut 10 000 €)
 *   warningTriggered = forecast30 < seuil
 */
final readonly class ComputeRevenueForecastKpiHandler
{
    private const float DEFAULT_PROBABILITY_COEFFICIENT = 0.3;
    private const float DEFAULT_WARNING_THRESHOLD_EUROS = 10_000.0;

    public function __construct(
        private RevenueForecastReadModelRepositoryInterface $repository,
        private RevenueForecastCalculator $calculator,
    ) {
    }

    public function __invoke(?DateTimeImmutable $now = null): RevenueForecastKpiDto
    {
        $now ??= new DateTimeImmutable();

        $records = $this->repository->findPipelineOrders($now);
        $forecast = $this->calculator->calculate(
            records: $records,
            probabilityCoefficient: self::DEFAULT_PROBABILITY_COEFFICIENT,
            now: $now,
        );

        return new RevenueForecastKpiDto(
            forecast30Euros: $forecast->getForecast30Euros(),
            forecast90Euros: $forecast->getForecast90Euros(),
            confirmedEuros: $forecast->getConfirmedCents() / 100,
            weightedQuotesEuros: $forecast->getWeightedQuotesCents() / 100,
            probabilityCoefficient: self::DEFAULT_PROBABILITY_COEFFICIENT,
            warningThresholdEuros: self::DEFAULT_WARNING_THRESHOLD_EUROS,
            warningTriggered: $forecast->getForecast30Euros() < self::DEFAULT_WARNING_THRESHOLD_EUROS
                && $forecast->getForecast30Euros() > 0.0,
        );
    }
}

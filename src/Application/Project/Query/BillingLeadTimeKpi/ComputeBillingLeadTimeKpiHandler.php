<?php

declare(strict_types=1);

namespace App\Application\Project\Query\BillingLeadTimeKpi;

use App\Domain\Project\Repository\BillingLeadTimeReadModelRepositoryInterface;
use App\Domain\Project\Service\BillingLeadTimeCalculator;
use App\Domain\Project\Service\QuoteInvoiceRecord;
use DateTimeImmutable;

/**
 * Compute billing lead time KPI widget data for the business dashboard
 * (US-111 T-111-04).
 *
 * Pattern aligné US-110 ComputeDsoKpiHandler.
 *
 * - 3 rolling windows (30/90/365 days) via BillingLeadTimeCalculator
 * - Top 3 clients : aggregation moyenne lead time sur fenêtre 30j
 * - Warning threshold default 14j (US-111 AC). Hierarchical override
 *   scope T-111-05 Slack alerting.
 */
final readonly class ComputeBillingLeadTimeKpiHandler
{
    public const float DEFAULT_WARNING_THRESHOLD_DAYS = 14.0;
    public const int TOP_SLOW_CLIENTS_LIMIT = 3;

    public function __construct(
        private BillingLeadTimeReadModelRepositoryInterface $repository,
        private BillingLeadTimeCalculator $calculator,
    ) {
    }

    public function __invoke(?DateTimeImmutable $now = null): BillingLeadTimeKpiDto
    {
        $now = $now ?? new DateTimeImmutable();

        $records30 = $this->repository->findEmittedInRollingWindow(30, $now);
        $records90 = $this->repository->findEmittedInRollingWindow(90, $now);
        $records365 = $this->repository->findEmittedInRollingWindow(365, $now);

        $stats30 = $this->calculator->calculateRolling($records30, 30, $now);
        $stats90 = $this->calculator->calculateRolling($records90, 90, $now);
        $stats365 = $this->calculator->calculateRolling($records365, 365, $now);

        return new BillingLeadTimeKpiDto(
            stats30: $stats30,
            stats90: $stats90,
            stats365: $stats365,
            topSlowClients: $this->buildTopSlowClients($records30),
            warningThresholdDays: self::DEFAULT_WARNING_THRESHOLD_DAYS,
            warningTriggered: $stats30->p50->getDays() > self::DEFAULT_WARNING_THRESHOLD_DAYS,
        );
    }

    /**
     * Aggregate lead times by client ID, sort by average DESC, take top N.
     *
     * @param list<QuoteInvoiceRecord> $records
     *
     * @return list<TopSlowClientDto>
     */
    private function buildTopSlowClients(array $records): array
    {
        /** @var array<int, array{name: string, sum: float, count: int}> $grouped */
        $grouped = [];

        foreach ($records as $record) {
            if ($record->clientId === null) {
                continue;
            }

            $key = $record->clientId;
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['name' => $record->clientName ?? '—', 'sum' => 0.0, 'count' => 0];
            }

            $grouped[$key]['sum'] += $record->leadTimeDays();
            ++$grouped[$key]['count'];
        }

        $averaged = [];
        foreach ($grouped as $clientId => $data) {
            $averaged[] = new TopSlowClientDto(
                clientId: $clientId,
                clientName: $data['name'],
                averageLeadTimeDays: round($data['sum'] / $data['count'], 1),
                sampleCount: $data['count'],
            );
        }

        usort(
            $averaged,
            static fn (TopSlowClientDto $a, TopSlowClientDto $b): int => $b->averageLeadTimeDays <=> $a->averageLeadTimeDays,
        );

        return array_slice($averaged, 0, self::TOP_SLOW_CLIENTS_LIMIT);
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\BillingLeadTimeReadModelRepositoryInterface;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Cache decorator for {@see BillingLeadTimeReadModelRepositoryInterface}.
 *
 * Pattern aligné avec {@see CachingDsoReadModelRepository} (US-110 T-110-03).
 * Caches `findEmittedInRollingWindow` results in the `cache.kpi` pool
 * (1h TTL configured globally). Invalidated by
 * {@see App\Application\Project\EventListener\InvalidateBillingLeadTimeCacheOnInvoiceCreated}.
 *
 * Cache key includes company id + window days + today's date — same
 * strategy as DSO decorator (rolling window rebuilds at day boundary).
 *
 * EPIC-003 Phase 4 sprint-024 US-111 T-111-03.
 */
final readonly class CachingBillingLeadTimeReadModelRepository implements BillingLeadTimeReadModelRepositoryInterface
{
    public function __construct(
        private BillingLeadTimeReadModelRepositoryInterface $inner,
        private CacheInterface $kpiCache,
        private CompanyContext $companyContext,
    ) {
    }

    public function findEmittedInRollingWindow(int $windowDays, DateTimeImmutable $now): array
    {
        $cacheKey = $this->buildCacheKey($windowDays, $now);

        return $this->kpiCache->get(
            $cacheKey,
            fn (ItemInterface $item): array => $this->inner->findEmittedInRollingWindow($windowDays, $now),
        );
    }

    public function findAllClientsAggregated(int $windowDays, DateTimeImmutable $now): array
    {
        $cacheKey = $this->buildClientsCacheKey($windowDays, $now);

        return $this->kpiCache->get(
            $cacheKey,
            fn (ItemInterface $item): array => $this->inner->findAllClientsAggregated($windowDays, $now),
        );
    }

    private function buildClientsCacheKey(int $windowDays, DateTimeImmutable $now): string
    {
        $companyId = $this->companyContext->getCurrentCompany()->getId() ?? 0;

        return sprintf(
            'billing_lead_time.clients_aggregated.company_%d.window_%d.day_%s',
            $companyId,
            $windowDays,
            $now->format('Y-m-d'),
        );
    }

    private function buildCacheKey(int $windowDays, DateTimeImmutable $now): string
    {
        $companyId = $this->companyContext->getCurrentCompany()->getId() ?? 0;

        return sprintf(
            'billing_lead_time.emitted_records.company_%d.window_%d.day_%s',
            $companyId,
            $windowDays,
            $now->format('Y-m-d'),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\ConversionRateReadModelRepositoryInterface;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Cache decorator for {@see ConversionRateReadModelRepositoryInterface} (US-115 T-115-03).
 *
 * Cache pool `cache.kpi` 1h TTL. Invalidé par
 * {@see \App\Application\Project\EventListener\InvalidateConversionRateCacheOnOrderStatusChanged}.
 *
 * Cache key : company id (multi-tenant) + jour courant (rebuild quotidien glissant).
 */
final readonly class CachingConversionRateReadModelRepository implements ConversionRateReadModelRepositoryInterface
{
    public function __construct(
        private ConversionRateReadModelRepositoryInterface $inner,
        private CacheInterface $kpiCache,
        private CompanyContext $companyContext,
    ) {
    }

    public function findConversionRecords(DateTimeImmutable $now): array
    {
        $cacheKey = $this->buildCacheKey($now);

        return $this->kpiCache->get(
            $cacheKey,
            fn (ItemInterface $item): array => $this->inner->findConversionRecords($now),
        );
    }

    public function findAllClientsAggregated(int $windowDays, DateTimeImmutable $now): array
    {
        $cacheKey = sprintf(
            'conversion_rate.clients_aggregated.company_%d.window_%d.day_%s',
            $this->companyContext->getCurrentCompany()->getId() ?? 0,
            $windowDays,
            $now->format('Y-m-d'),
        );

        return $this->kpiCache->get(
            $cacheKey,
            fn (ItemInterface $item): array => $this->inner->findAllClientsAggregated($windowDays, $now),
        );
    }

    private function buildCacheKey(DateTimeImmutable $now): string
    {
        $companyId = $this->companyContext->getCurrentCompany()->getId() ?? 0;

        return sprintf(
            'conversion_rate.records.company_%d.day_%s',
            $companyId,
            $now->format('Y-m-d'),
        );
    }
}

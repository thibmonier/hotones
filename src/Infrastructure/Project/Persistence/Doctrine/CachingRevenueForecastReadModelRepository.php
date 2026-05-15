<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\RevenueForecastReadModelRepositoryInterface;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Cache decorator for {@see RevenueForecastReadModelRepositoryInterface} (US-114 T-114-03).
 *
 * Caches `findPipelineOrders` results in the `cache.kpi` pool (1h TTL).
 * Invalidated by {@see \App\Application\Project\EventListener\InvalidateRevenueForecastCacheOnOrderStatusChanged}
 * and {@see \App\Application\Project\EventListener\InvalidateRevenueForecastCacheOnInvoiceCreated}.
 *
 * Cache key includes company id (multi-tenant) + today's date (horizon
 * glissant rebuild quotidien).
 */
final readonly class CachingRevenueForecastReadModelRepository implements RevenueForecastReadModelRepositoryInterface
{
    public function __construct(
        private RevenueForecastReadModelRepositoryInterface $inner,
        private CacheInterface $kpiCache,
        private CompanyContext $companyContext,
    ) {
    }

    public function findPipelineOrders(DateTimeImmutable $now): array
    {
        $cacheKey = $this->buildCacheKey($now);

        return $this->kpiCache->get(
            $cacheKey,
            fn (ItemInterface $item): array => $this->inner->findPipelineOrders($now),
        );
    }

    private function buildCacheKey(DateTimeImmutable $now): string
    {
        $companyId = $this->companyContext->getCurrentCompany()->getId() ?? 0;

        return sprintf(
            'revenue_forecast.pipeline.company_%d.day_%s',
            $companyId,
            $now->format('Y-m-d'),
        );
    }
}

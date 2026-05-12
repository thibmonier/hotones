<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\DsoReadModelRepositoryInterface;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Cache decorator for {@see DsoReadModelRepositoryInterface}.
 *
 * Caches `findPaidInRollingWindow` results in the `cache.kpi` pool (1h
 * TTL configured globally) — DSO changes slowly, fresh reads every hour
 * are sufficient. Invalidated by {@see InvalidateDsoCacheOnInvoicePaid}.
 *
 * Cache key includes company id (multi-tenant) + window days + today's
 * date (rolling window must rebuild at day boundary).
 *
 * EPIC-003 Phase 4 sprint-024 US-110 T-110-03.
 */
final readonly class CachingDsoReadModelRepository implements DsoReadModelRepositoryInterface
{
    public function __construct(
        private DsoReadModelRepositoryInterface $inner,
        private CacheInterface $kpiCache,
        private CompanyContext $companyContext,
    ) {
    }

    public function findPaidInRollingWindow(int $windowDays, DateTimeImmutable $now): array
    {
        $cacheKey = $this->buildCacheKey($windowDays, $now);

        return $this->kpiCache->get(
            $cacheKey,
            fn (ItemInterface $item): array => $this->inner->findPaidInRollingWindow($windowDays, $now),
        );
    }

    private function buildCacheKey(int $windowDays, DateTimeImmutable $now): string
    {
        $companyId = $this->companyContext->getCurrentCompany()->getId() ?? 0;

        return sprintf(
            'dso.paid_records.company_%d.window_%d.day_%s',
            $companyId,
            $windowDays,
            $now->format('Y-m-d'),
        );
    }
}

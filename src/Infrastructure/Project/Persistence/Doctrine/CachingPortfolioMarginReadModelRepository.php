<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\PortfolioMarginReadModelRepositoryInterface;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Cache decorator for {@see PortfolioMarginReadModelRepositoryInterface} (US-117 T-117-03).
 *
 * Cache pool `cache.kpi` 1h TTL. Invalidé par
 * {@see \App\Application\Project\EventListener\InvalidatePortfolioMarginCacheOnProjectMarginRecalculated}
 * sur chaque {@see \App\Domain\Project\Event\ProjectMarginRecalculatedEvent}.
 *
 * Clé : `portfolio_margin.snapshot.company_%d.day_%s` — multi-tenant + rotation
 * journalière (filet de sécurité si event miss).
 */
final readonly class CachingPortfolioMarginReadModelRepository implements PortfolioMarginReadModelRepositoryInterface
{
    public function __construct(
        private PortfolioMarginReadModelRepositoryInterface $inner,
        private CacheInterface $kpiCache,
        private CompanyContext $companyContext,
    ) {
    }

    public function findActiveProjectsWithSnapshot(DateTimeImmutable $now): array
    {
        $cacheKey = $this->buildCacheKey($now);

        return $this->kpiCache->get(
            $cacheKey,
            fn (ItemInterface $item): array => $this->inner->findActiveProjectsWithSnapshot($now),
        );
    }

    private function buildCacheKey(DateTimeImmutable $now): string
    {
        $companyId = $this->companyContext->getCurrentCompany()->getId() ?? 0;

        return sprintf(
            'portfolio_margin.snapshot.company_%d.day_%s',
            $companyId,
            $now->format('Y-m-d'),
        );
    }
}

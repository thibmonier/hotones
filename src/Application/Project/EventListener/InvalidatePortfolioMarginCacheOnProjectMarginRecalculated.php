<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Domain\Project\Event\ProjectMarginRecalculatedEvent;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Invalide le cache.kpi (portfolio margin snapshot) sur recalcul marge projet
 * (US-117 T-117-03).
 *
 * Chaque {@see ProjectMarginRecalculatedEvent} signifie qu'un snapshot projet
 * a changé → la moyenne pondérée portefeuille doit être recomputée. Clear
 * complet du pool (simple, conservateur ; volume KPI faible).
 */
#[AsMessageHandler]
final readonly class InvalidatePortfolioMarginCacheOnProjectMarginRecalculated
{
    public function __construct(
        private CacheItemPoolInterface $kpiCache,
    ) {
    }

    public function __invoke(ProjectMarginRecalculatedEvent $event): void
    {
        $this->kpiCache->clear();
    }
}

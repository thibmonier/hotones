<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Domain\Order\Event\OrderStatusChangedEvent;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Invalide le cache.kpi (forecast pipeline) sur changement statut Order (US-114 T-114-03).
 *
 * Changement statut Order (a_signer → signe / gagne / perdu / ...) impacte
 * la composition du pipeline forecast — clear cache pour recompute.
 */
#[AsMessageHandler]
final readonly class InvalidateRevenueForecastCacheOnOrderStatusChanged
{
    public function __construct(
        private CacheItemPoolInterface $kpiCache,
    ) {
    }

    public function __invoke(OrderStatusChangedEvent $event): void
    {
        $this->kpiCache->clear();
    }
}

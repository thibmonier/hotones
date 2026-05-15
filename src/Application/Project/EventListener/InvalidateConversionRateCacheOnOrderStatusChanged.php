<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Domain\Order\Event\OrderStatusChangedEvent;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Invalide le cache.kpi (conversion rate) sur changement statut Order (US-115 T-115-03).
 *
 * Toute transition statut peut impacter le taux de conversion (a_signer →
 * signe/gagne = succès ; → perdu/abandonne = échec). Clear pour recompute.
 */
#[AsMessageHandler]
final readonly class InvalidateConversionRateCacheOnOrderStatusChanged
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

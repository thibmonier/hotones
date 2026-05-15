<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Domain\Invoice\Event\InvoiceCreatedEvent;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Invalide le cache.kpi (forecast pipeline) sur création facture (US-114 T-114-03).
 *
 * Création Invoice → l'Order associé sort du forecast (déjà facturé) ;
 * clear cache pour recompute (anti double comptage).
 */
#[AsMessageHandler]
final readonly class InvalidateRevenueForecastCacheOnInvoiceCreated
{
    public function __construct(
        private CacheItemPoolInterface $kpiCache,
    ) {
    }

    public function __invoke(InvoiceCreatedEvent $event): void
    {
        $this->kpiCache->clear();
    }
}

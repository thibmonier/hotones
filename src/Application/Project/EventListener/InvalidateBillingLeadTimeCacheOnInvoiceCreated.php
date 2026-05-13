<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Domain\Invoice\Event\InvoiceCreatedEvent;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Invalidates the KPI cache pool when a new invoice is created (emitted).
 *
 * Triggered by {@see InvoiceCreatedEvent} domain event. Clears the
 * `cache.kpi` pool so US-111 billing lead time (and any other KPI
 * sharing the pool such as US-110 DSO and US-112 margin adoption) is
 * recomputed on next read.
 *
 * Pattern aligné avec {@see InvalidateDsoCacheOnInvoicePaid} (US-110 T-110-03).
 *
 * EPIC-003 Phase 4 sprint-024 US-111 T-111-03.
 *
 * Note: pool clear is preferred over targeted key deletion — keys depend
 * on rolling window day boundaries (multiple variants) and downstream
 * KPIs share invalidation. 1h re-warm cost negligible.
 */
#[AsMessageHandler]
final readonly class InvalidateBillingLeadTimeCacheOnInvoiceCreated
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

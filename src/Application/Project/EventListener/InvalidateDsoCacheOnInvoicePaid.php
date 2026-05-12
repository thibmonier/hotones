<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Domain\Invoice\Event\InvoicePaidEvent;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Message handler invalidating the KPI cache pool when an invoice is paid.
 *
 * Triggered by {@see InvoicePaidEvent} domain event. Clears the
 * `cache.kpi` pool so that DSO (US-110), billing lead time (US-111)
 * and margin adoption (US-112) KPIs are recomputed on next read.
 *
 * EPIC-003 Phase 4 sprint-024 US-110 T-110-03.
 *
 * Note: pool clear is preferred over targeted key deletion because:
 * - keys depend on rolling window day boundaries (multiple variants)
 * - downstream KPIs (US-111/US-112) likely share same invalidation
 * - cost is negligible (pool size small, 1h re-warm)
 */
#[AsMessageHandler]
final readonly class InvalidateDsoCacheOnInvoicePaid
{
    public function __construct(
        private CacheItemPoolInterface $kpiCache,
    ) {
    }

    public function __invoke(InvoicePaidEvent $event): void
    {
        $this->kpiCache->clear();
    }
}

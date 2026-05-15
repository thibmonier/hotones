<?php

declare(strict_types=1);

namespace App\Domain\Project\Repository;

use App\Domain\Project\Service\ClientBillingLeadTimeAggregate;
use App\Domain\Project\Service\QuoteInvoiceRecord;
use DateTimeImmutable;

/**
 * Read-model repository for billing lead time computation (US-111).
 *
 * Provides projection of signed-quote ↔ emitted-invoice pairs into
 * {@see QuoteInvoiceRecord} DTOs consumed by
 * {@see App\Domain\Project\Service\BillingLeadTimeCalculator}.
 *
 * Multi-tenant aware: implementations MUST filter by current company.
 */
interface BillingLeadTimeReadModelRepositoryInterface
{
    /**
     * Find every (quote signed, invoice emitted) pair whose invoice was
     * emitted within the rolling window `[now - windowDays, now]` for
     * the current company.
     *
     * Quotes without an associated invoice are excluded (counted separately
     * as “billing backlog” — out of scope here).
     *
     * Cancelled invoices and drafts are excluded.
     *
     * @return list<QuoteInvoiceRecord>
     */
    public function findEmittedInRollingWindow(int $windowDays, DateTimeImmutable $now): array;

    /**
     * Find billing lead time aggregated by client within the rolling window
     * (US-116 drill-down).
     *
     * Retourne pour chaque client le lead time moyen + le nombre de devis
     * convertis facturés sur la fenêtre. Tri valeur décroissante.
     *
     * @return list<ClientBillingLeadTimeAggregate>
     */
    public function findAllClientsAggregated(int $windowDays, DateTimeImmutable $now): array;
}

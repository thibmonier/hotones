<?php

declare(strict_types=1);

namespace App\Domain\Project\Repository;

use App\Domain\Project\Service\InvoicePaymentRecord;
use DateTimeImmutable;

/**
 * Read-model repository for DSO (Days Sales Outstanding) calculation.
 *
 * Provides projection of paid invoices into {@see InvoicePaymentRecord}
 * lightweight DTOs consumed by {@see App\Domain\Project\Service\DsoCalculator}.
 *
 * Multi-tenant aware: implementations MUST filter by current company.
 */
interface DsoReadModelRepositoryInterface
{
    /**
     * Find all paid invoices whose payment date falls within the rolling
     * window `[now - windowDays, now]` for the current company.
     *
     * Unpaid invoices (paidAt IS NULL) are excluded.
     * Cancelled invoices are excluded.
     *
     * @return list<InvoicePaymentRecord>
     */
    public function findPaidInRollingWindow(int $windowDays, DateTimeImmutable $now): array;
}

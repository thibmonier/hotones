<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use App\Domain\Project\ValueObject\DsoDays;
use DateTimeImmutable;

/**
 * Days Sales Outstanding calculator — domaine pure, sans dépendance Doctrine.
 *
 * Formule : DSO = Σ(delay_days × amount_paid) / Σ(amount_paid)
 *
 * - Factures impayées (paidAt === null OU amountPaidCents === 0) exclues
 * - Window rolling : seules les factures payées dans les `windowDays`
 *   derniers jours sont considérées
 *
 * Pattern réutilisé sprint-022 MarginCalculator (Domain Service pure
 * testable Unit sans BDD).
 */
final readonly class DsoCalculator
{
    /**
     * @param iterable<InvoicePaymentRecord> $invoices
     */
    public function calculateRolling(
        iterable $invoices,
        int $windowDays,
        DateTimeImmutable $now,
    ): DsoDays {
        $windowStart = $now->modify(sprintf('-%d days', $windowDays));

        $weightedDelaySum = 0.0;
        $amountSum = 0;

        foreach ($invoices as $invoice) {
            if (!$invoice->isPaid()) {
                continue;
            }

            // Garanti non-null par isPaid()
            if ($invoice->paidAt < $windowStart) {
                continue;
            }

            $delay = $invoice->paymentDelayDays();
            $weightedDelaySum += $delay * $invoice->amountPaidCents;
            $amountSum += $invoice->amountPaidCents;
        }

        if ($amountSum === 0) {
            return DsoDays::zero();
        }

        return DsoDays::fromDays($weightedDelaySum / $amountSum);
    }
}

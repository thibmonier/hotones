<?php

declare(strict_types=1);

namespace App\Infrastructure\Invoice\Translator;

use App\Domain\Invoice\Entity\Invoice as DddInvoice;
use App\Entity\Invoice as FlatInvoice;

/**
 * Anti-Corruption Layer translator (DDD → flat) for the Invoice BC.
 *
 * Stateless service.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 */
final class InvoiceDddToFlatTranslator
{
    public function applyTo(DddInvoice $ddd, FlatInvoice $flat): void
    {
        // Status mapping is 1-to-1 (same string values)
        $flat->status = $ddd->getStatus()->value;

        $flat->amountHt = (string) $ddd->getAmountHt()->getAmount();
        $flat->amountTva = (string) $ddd->getAmountTva()->getAmount();
        $flat->amountTtc = (string) $ddd->getAmountTtc()->getAmount();
        $flat->paymentTerms = $ddd->getPaymentTerms();
    }
}

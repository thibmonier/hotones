<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Read-model DTO for billing lead time calculation (US-111).
 *
 * Représente un devis (Order/Quote) signé qui a généré une facture émise.
 * Hydraté par le Repository depuis la jointure Order ↔ Invoice ; pas de
 * dépendance Doctrine côté Domain.
 *
 * `clientId` optionnel : utilisé par {@see BillingLeadTimeCalculator}
 * pour calculer top 3 clients lents (T-111-02 / T-111-04). Null si
 * agrégation globale uniquement.
 */
final readonly class QuoteInvoiceRecord
{
    public function __construct(
        public DateTimeImmutable $signedAt,
        public DateTimeImmutable $emittedAt,
        public ?int $clientId = null,
        public ?string $clientName = null,
    ) {
        if ($emittedAt < $signedAt) {
            throw new InvalidArgumentException('Invoice emittedAt cannot be before quote signedAt');
        }
    }

    public function leadTimeDays(): float
    {
        $secondsPerDay = 86_400;
        $diff = $this->emittedAt->getTimestamp() - $this->signedAt->getTimestamp();

        return $diff / $secondsPerDay;
    }
}

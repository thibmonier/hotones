<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Read-model DTO for DSO calculation.
 *
 * Domain pure — pas de référence Doctrine. Le Repository hydrate cette
 * structure depuis l'aggregate Invoice ou via SQL projection.
 */
final readonly class InvoicePaymentRecord
{
    public function __construct(
        public DateTimeImmutable $issuedAt,
        public ?DateTimeImmutable $paidAt,
        public int $amountPaidCents,
    ) {
        if ($amountPaidCents < 0) {
            throw new InvalidArgumentException('Amount paid cannot be negative');
        }
    }

    public function isPaid(): bool
    {
        return $this->paidAt !== null && $this->amountPaidCents > 0;
    }

    public function paymentDelayDays(): float
    {
        if ($this->paidAt === null) {
            return 0.0;
        }

        $secondsPerDay = 86_400;
        $diff = $this->paidAt->getTimestamp() - $this->issuedAt->getTimestamp();

        return $diff / $secondsPerDay;
    }
}

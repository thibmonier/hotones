<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Event;

use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;

/**
 * Domain event raised when an invoice is paid.
 */
final readonly class InvoicePaidEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        private InvoiceId $invoiceId,
        private Money $amountPaid,
        private DateTimeImmutable $paidAt,
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public static function create(
        InvoiceId $invoiceId,
        Money $amountPaid,
        DateTimeImmutable $paidAt,
    ): self {
        return new self($invoiceId, $amountPaid, $paidAt);
    }

    public function getInvoiceId(): InvoiceId
    {
        return $this->invoiceId;
    }

    public function getAmountPaid(): Money
    {
        return $this->amountPaid;
    }

    public function getPaidAt(): DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

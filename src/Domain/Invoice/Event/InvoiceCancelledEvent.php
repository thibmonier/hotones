<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Event;

use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Shared\Interface\DomainEventInterface;
use DateTimeImmutable;

/**
 * Domain event raised when an invoice is cancelled.
 */
final readonly class InvoiceCancelledEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        private InvoiceId $invoiceId,
        private string $reason,
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public static function create(InvoiceId $invoiceId, string $reason): self
    {
        return new self($invoiceId, $reason);
    }

    public function getInvoiceId(): InvoiceId
    {
        return $this->invoiceId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

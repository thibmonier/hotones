<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Event;

use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Shared\Interface\DomainEventInterface;

/**
 * Domain event raised when an invoice is issued (sent to client).
 */
final readonly class InvoiceIssuedEvent implements DomainEventInterface
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private InvoiceId $invoiceId,
        private \DateTimeImmutable $issuedAt,
        private \DateTimeImmutable $dueDate,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public static function create(
        InvoiceId $invoiceId,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $dueDate,
    ): self {
        return new self($invoiceId, $issuedAt, $dueDate);
    }

    public function getInvoiceId(): InvoiceId
    {
        return $this->invoiceId;
    }

    public function getIssuedAt(): \DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function getDueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

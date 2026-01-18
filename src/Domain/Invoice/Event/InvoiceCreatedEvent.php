<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Event;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Shared\Interface\DomainEventInterface;

/**
 * Domain event raised when an invoice is created.
 */
final readonly class InvoiceCreatedEvent implements DomainEventInterface
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private InvoiceId $invoiceId,
        private InvoiceNumber $invoiceNumber,
        private CompanyId $companyId,
        private ClientId $clientId,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public static function create(
        InvoiceId $invoiceId,
        InvoiceNumber $invoiceNumber,
        CompanyId $companyId,
        ClientId $clientId,
    ): self {
        return new self($invoiceId, $invoiceNumber, $companyId, $clientId);
    }

    public function getInvoiceId(): InvoiceId
    {
        return $this->invoiceId;
    }

    public function getInvoiceNumber(): InvoiceNumber
    {
        return $this->invoiceNumber;
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Event;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;

/**
 * Domain event raised when an invoice is created.
 *
 * Sprint-021 US-101 (AT-3.2 ADR-0016) : payload étendu avec
 * `array<WorkItemId> $workItemIds` (default empty pour backward compat).
 * Permet listener `BillRelatedWorkItemsOnInvoiceCreated` de transiter les
 * WorkItems associés vers status `billed` sans query DB extra.
 *
 * Caller (Application Layer use case `CreateInvoice`) collecte les WorkItem
 * IDs (typiquement WorkItems `validated` non encore facturés du Project)
 * AVANT dispatch event.
 */
final readonly class InvoiceCreatedEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredOn;

    /**
     * @param list<WorkItemId> $workItemIds
     */
    public function __construct(
        private InvoiceId $invoiceId,
        private InvoiceNumber $invoiceNumber,
        private CompanyId $companyId,
        private ClientId $clientId,
        private array $workItemIds = [],
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    /**
     * @param list<WorkItemId> $workItemIds
     */
    public static function create(
        InvoiceId $invoiceId,
        InvoiceNumber $invoiceNumber,
        CompanyId $companyId,
        ClientId $clientId,
        array $workItemIds = [],
    ): self {
        return new self($invoiceId, $invoiceNumber, $companyId, $clientId, $workItemIds);
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

    /**
     * @return list<WorkItemId>
     */
    public function getWorkItemIds(): array
    {
        return $this->workItemIds;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

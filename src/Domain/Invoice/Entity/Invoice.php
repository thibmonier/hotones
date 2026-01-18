<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Invoice\Event\InvoiceCancelledEvent;
use App\Domain\Invoice\Event\InvoiceCreatedEvent;
use App\Domain\Invoice\Event\InvoiceIssuedEvent;
use App\Domain\Invoice\Event\InvoicePaidEvent;
use App\Domain\Invoice\Exception\InvalidInvoiceException;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Invoice\ValueObject\InvoiceLineId;
use App\Domain\Invoice\ValueObject\InvoiceNumber;
use App\Domain\Invoice\ValueObject\InvoiceStatus;
use App\Domain\Invoice\ValueObject\TaxRate;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Trait\RecordsDomainEvents;
use App\Domain\Shared\ValueObject\Money;

/**
 * Invoice aggregate root.
 *
 * Represents an invoice with line items, amounts, and payment tracking.
 */
final class Invoice implements AggregateRootInterface
{
    use RecordsDomainEvents;

    private InvoiceId $id;
    private InvoiceNumber $number;
    private CompanyId $companyId;
    private ClientId $clientId;
    private ?OrderId $orderId;
    private ?ProjectId $projectId;
    private InvoiceStatus $status;
    private Money $amountHt;
    private Money $amountTva;
    private Money $amountTtc;
    private ?string $notes;
    private ?string $paymentTerms;
    private ?\DateTimeImmutable $issuedAt;
    private ?\DateTimeImmutable $dueDate;
    private ?\DateTimeImmutable $paidAt;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    /** @var array<InvoiceLine> */
    private array $lines = [];

    private function __construct(
        InvoiceId $id,
        InvoiceNumber $number,
        CompanyId $companyId,
        ClientId $clientId,
        ?OrderId $orderId,
        ?ProjectId $projectId,
    ) {
        $this->id = $id;
        $this->number = $number;
        $this->companyId = $companyId;
        $this->clientId = $clientId;
        $this->orderId = $orderId;
        $this->projectId = $projectId;
        $this->status = InvoiceStatus::DRAFT;
        $this->amountHt = Money::zero();
        $this->amountTva = Money::zero();
        $this->amountTtc = Money::zero();
        $this->notes = null;
        $this->paymentTerms = null;
        $this->issuedAt = null;
        $this->dueDate = null;
        $this->paidAt = null;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(
        InvoiceId $id,
        InvoiceNumber $number,
        CompanyId $companyId,
        ClientId $clientId,
        ?OrderId $orderId = null,
        ?ProjectId $projectId = null,
    ): self {
        $invoice = new self($id, $number, $companyId, $clientId, $orderId, $projectId);

        $invoice->recordEvent(
            InvoiceCreatedEvent::create($id, $number, $companyId, $clientId)
        );

        return $invoice;
    }

    // Line management

    public function addLine(
        InvoiceLineId $lineId,
        string $description,
        float $quantity,
        Money $unitPriceHt,
        TaxRate $taxRate,
    ): void {
        $this->ensureEditable();

        $position = count($this->lines) + 1;
        $line = InvoiceLine::create($lineId, $description, $quantity, $unitPriceHt, $taxRate, $position);

        $this->lines[] = $line;
        $this->recalculateTotals();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateLine(
        InvoiceLineId $lineId,
        string $description,
        float $quantity,
        Money $unitPriceHt,
        TaxRate $taxRate,
    ): void {
        $this->ensureEditable();

        $line = $this->findLine($lineId);
        $line->update($description, $quantity, $unitPriceHt, $taxRate);

        $this->recalculateTotals();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function removeLine(InvoiceLineId $lineId): void
    {
        $this->ensureEditable();

        $index = $this->findLineIndex($lineId);
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);

        $this->reorderLines();
        $this->recalculateTotals();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Status transitions

    public function issue(\DateTimeImmutable $issuedAt, \DateTimeImmutable $dueDate): void
    {
        if (!$this->status->canTransitionTo(InvoiceStatus::SENT)) {
            throw InvalidInvoiceException::invalidStatusTransition($this->status, InvoiceStatus::SENT);
        }

        if (count($this->lines) === 0) {
            throw InvalidInvoiceException::emptyLines();
        }

        if ($dueDate < $issuedAt) {
            throw InvalidInvoiceException::dueDateBeforeIssueDate();
        }

        $this->status = InvoiceStatus::SENT;
        $this->issuedAt = $issuedAt;
        $this->dueDate = $dueDate;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(InvoiceIssuedEvent::create($this->id, $issuedAt, $dueDate));
    }

    public function markAsPaid(\DateTimeImmutable $paidAt, Money $amountPaid): void
    {
        if (!$this->status->canTransitionTo(InvoiceStatus::PAID)) {
            throw InvalidInvoiceException::invalidStatusTransition($this->status, InvoiceStatus::PAID);
        }

        if (!$amountPaid->isPositive()) {
            throw InvalidInvoiceException::invalidPaymentAmount();
        }

        $this->status = InvoiceStatus::PAID;
        $this->paidAt = $paidAt;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(InvoicePaidEvent::create($this->id, $amountPaid, $paidAt));
    }

    public function markAsOverdue(): void
    {
        if (!$this->status->canTransitionTo(InvoiceStatus::OVERDUE)) {
            throw InvalidInvoiceException::invalidStatusTransition($this->status, InvoiceStatus::OVERDUE);
        }

        $this->status = InvoiceStatus::OVERDUE;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function cancel(string $reason): void
    {
        if ($this->status === InvoiceStatus::PAID) {
            throw InvalidInvoiceException::cannotCancelPaidInvoice();
        }

        if (!$this->status->canTransitionTo(InvoiceStatus::CANCELLED)) {
            throw InvalidInvoiceException::invalidStatusTransition($this->status, InvoiceStatus::CANCELLED);
        }

        $this->status = InvoiceStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(InvoiceCancelledEvent::create($this->id, $reason));
    }

    // Updates

    public function setNotes(?string $notes): void
    {
        $this->ensureEditable();
        $this->notes = $notes;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setPaymentTerms(?string $paymentTerms): void
    {
        $this->ensureEditable();
        $this->paymentTerms = $paymentTerms;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Validation helpers

    private function ensureEditable(): void
    {
        if (!$this->status->isEditable()) {
            throw InvalidInvoiceException::cannotModifyFinalizedInvoice();
        }
    }

    private function findLine(InvoiceLineId $lineId): InvoiceLine
    {
        foreach ($this->lines as $line) {
            if ($line->getId()->equals($lineId)) {
                return $line;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Invoice line with ID %s not found', $lineId->getValue())
        );
    }

    private function findLineIndex(InvoiceLineId $lineId): int
    {
        foreach ($this->lines as $index => $line) {
            if ($line->getId()->equals($lineId)) {
                return $index;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Invoice line with ID %s not found', $lineId->getValue())
        );
    }

    private function recalculateTotals(): void
    {
        $totalHt = Money::zero();
        $totalTva = Money::zero();

        foreach ($this->lines as $line) {
            $totalHt = $totalHt->add($line->getTotalHt());
            $totalTva = $totalTva->add($line->getTaxAmount());
        }

        $this->amountHt = $totalHt;
        $this->amountTva = $totalTva;
        $this->amountTtc = $totalHt->add($totalTva);
    }

    private function reorderLines(): void
    {
        $position = 1;
        foreach ($this->lines as $line) {
            $line->updatePosition($position);
            $position++;
        }
    }

    // Query methods

    public function isOverdue(): bool
    {
        if ($this->status !== InvoiceStatus::SENT) {
            return false;
        }

        if ($this->dueDate === null) {
            return false;
        }

        return $this->dueDate < new \DateTimeImmutable('today');
    }

    public function getDaysUntilDue(): ?int
    {
        if ($this->dueDate === null) {
            return null;
        }

        $now = new \DateTimeImmutable('today');
        $diff = $now->diff($this->dueDate);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    // Getters

    public function getId(): InvoiceId
    {
        return $this->id;
    }

    public function getNumber(): InvoiceNumber
    {
        return $this->number;
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getOrderId(): ?OrderId
    {
        return $this->orderId;
    }

    public function getProjectId(): ?ProjectId
    {
        return $this->projectId;
    }

    public function getStatus(): InvoiceStatus
    {
        return $this->status;
    }

    public function getAmountHt(): Money
    {
        return $this->amountHt;
    }

    public function getAmountTva(): Money
    {
        return $this->amountTva;
    }

    public function getAmountTtc(): Money
    {
        return $this->amountTtc;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    public function getIssuedAt(): ?\DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return array<InvoiceLine>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getLineCount(): int
    {
        return count($this->lines);
    }
}

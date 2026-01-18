<?php

declare(strict_types=1);

namespace App\Domain\Order\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Order\Event\OrderCreatedEvent;
use App\Domain\Order\Event\OrderStatusChangedEvent;
use App\Domain\Order\Exception\InvalidOrderStatusTransitionException;
use App\Domain\Order\ValueObject\ContractType;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderLineId;
use App\Domain\Order\ValueObject\OrderLineType;
use App\Domain\Order\ValueObject\OrderSectionId;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Trait\RecordsDomainEvents;
use App\Domain\Shared\ValueObject\Money;

final class Order implements AggregateRootInterface
{
    use RecordsDomainEvents;

    private OrderId $id;
    private string $reference;
    private ClientId $clientId;
    private ?string $title;
    private ?string $description;
    private OrderStatus $status;
    private ContractType $contractType;
    private Money $amount;
    private ?Money $discount;
    private ?\DateTimeImmutable $startDate;
    private ?\DateTimeImmutable $endDate;
    private ?\DateTimeImmutable $signedAt;
    private ?string $notes;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    /** @var array<OrderSection> */
    private array $sections = [];

    private function __construct(
        OrderId $id,
        string $reference,
        ClientId $clientId,
        ContractType $contractType,
        Money $amount,
    ) {
        $this->id = $id;
        $this->reference = $reference;
        $this->clientId = $clientId;
        $this->contractType = $contractType;
        $this->amount = $amount;
        $this->status = OrderStatus::DRAFT;
        $this->title = null;
        $this->description = null;
        $this->discount = null;
        $this->startDate = null;
        $this->endDate = null;
        $this->signedAt = null;
        $this->notes = null;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(
        OrderId $id,
        string $reference,
        ClientId $clientId,
        ContractType $contractType,
        Money $amount,
    ): self {
        $order = new self($id, $reference, $clientId, $contractType, $amount);

        $order->recordEvent(
            OrderCreatedEvent::create($id, $clientId, $reference)
        );

        return $order;
    }

    public function updateDetails(
        ?string $title,
        ?string $description,
        ?Money $discount,
    ): void {
        $this->title = $title;
        $this->description = $description;
        $this->discount = $discount;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setDates(
        ?\DateTimeImmutable $startDate,
        ?\DateTimeImmutable $endDate,
    ): void {
        if ($startDate !== null && $endDate !== null && $startDate > $endDate) {
            throw new \InvalidArgumentException('Start date cannot be after end date');
        }

        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeStatus(OrderStatus $newStatus): void
    {
        if ($this->status === $newStatus) {
            return;
        }

        if (!$this->status->canTransitionTo($newStatus)) {
            throw InvalidOrderStatusTransitionException::create($this->status, $newStatus);
        }

        $previousStatus = $this->status;
        $this->status = $newStatus;
        $this->updatedAt = new \DateTimeImmutable();

        if ($newStatus === OrderStatus::SIGNED) {
            $this->signedAt = new \DateTimeImmutable();
        }

        $this->recordEvent(
            OrderStatusChangedEvent::create($this->id, $previousStatus, $newStatus)
        );
    }

    public function updateAmount(Money $amount): void
    {
        if ($this->status->isClosed()) {
            throw new \DomainException('Cannot update amount of a closed order');
        }

        $this->amount = $amount;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function applyDiscount(Money $discount): void
    {
        if ($discount->isGreaterThan($this->amount)) {
            throw new \InvalidArgumentException('Discount cannot be greater than order amount');
        }

        $this->discount = $discount;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addNotes(string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Section management

    public function addSection(
        OrderSectionId $sectionId,
        string $title,
    ): void {
        $sectionPosition = count($this->sections) + 1;
        $section = OrderSection::create($sectionId, $title, $sectionPosition);

        $this->sections[] = $section;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateSection(
        OrderSectionId $sectionId,
        string $title,
    ): void {
        $section = $this->findSection($sectionId);
        $section->update($title);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function removeSection(OrderSectionId $sectionId): void
    {
        $index = $this->findSectionIndex($sectionId);
        unset($this->sections[$index]);
        $this->sections = array_values($this->sections);

        $this->reorderSections();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Line management through sections

    public function addLineToSection(
        OrderSectionId $sectionId,
        OrderLineId $lineId,
        string $description,
        OrderLineType $type,
        float $quantity,
        Money $unitPriceHt,
        float $taxRate,
    ): void {
        $section = $this->findSection($sectionId);
        $section->addLine($lineId, $description, $type, $quantity, $unitPriceHt, $taxRate);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateLineInSection(
        OrderSectionId $sectionId,
        OrderLineId $lineId,
        string $description,
        OrderLineType $type,
        float $quantity,
        Money $unitPriceHt,
        float $taxRate,
    ): void {
        $section = $this->findSection($sectionId);
        $section->updateLine($lineId, $description, $type, $quantity, $unitPriceHt, $taxRate);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function removeLineFromSection(
        OrderSectionId $sectionId,
        OrderLineId $lineId,
    ): void {
        $section = $this->findSection($sectionId);
        $section->removeLine($lineId);
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Helper methods

    private function findSection(OrderSectionId $sectionId): OrderSection
    {
        foreach ($this->sections as $section) {
            if ($section->getId()->equals($sectionId)) {
                return $section;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Order section with ID %s not found in order', $sectionId->getValue())
        );
    }

    private function findSectionIndex(OrderSectionId $sectionId): int
    {
        foreach ($this->sections as $index => $section) {
            if ($section->getId()->equals($sectionId)) {
                return $index;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Order section with ID %s not found in order', $sectionId->getValue())
        );
    }

    private function reorderSections(): void
    {
        $position = 1;
        foreach ($this->sections as $section) {
            $section->updatePosition($position);
            $position++;
        }
    }

    // Calculated values from sections

    /**
     * Calculate total HT for this order (sum of all sections).
     */
    public function getTotalHt(): Money
    {
        $total = Money::zero();

        foreach ($this->sections as $section) {
            $total = $total->add($section->getTotalHt());
        }

        return $total;
    }

    /**
     * Calculate total tax amount for this order.
     */
    public function getTotalTaxAmount(): Money
    {
        $total = Money::zero();

        foreach ($this->sections as $section) {
            $total = $total->add($section->getTaxAmount());
        }

        return $total;
    }

    /**
     * Calculate total TTC for this order (HT + tax).
     */
    public function getTotalTtc(): Money
    {
        return $this->getTotalHt()->add($this->getTotalTaxAmount());
    }

    // Calculated values

    public function getNetAmount(): Money
    {
        if ($this->discount === null) {
            return $this->amount;
        }

        return $this->amount->subtract($this->discount);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    public function isSigned(): bool
    {
        return $this->signedAt !== null;
    }

    // Getters

    public function getId(): OrderId
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getContractType(): ContractType
    {
        return $this->contractType;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getDiscount(): ?Money
    {
        return $this->discount;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getSignedAt(): ?\DateTimeImmutable
    {
        return $this->signedAt;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
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
     * @return array<OrderSection>
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    public function getSectionCount(): int
    {
        return count($this->sections);
    }
}

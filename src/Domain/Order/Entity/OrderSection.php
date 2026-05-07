<?php

declare(strict_types=1);

namespace App\Domain\Order\Entity;

use App\Domain\Order\ValueObject\OrderLineId;
use App\Domain\Order\ValueObject\OrderLineType;
use App\Domain\Order\ValueObject\OrderSectionId;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Order section entity (child of Order aggregate).
 *
 * Represents a grouping of order lines within an order.
 * Sections allow organizing lines into logical groups (e.g., by service type, phase, etc.).
 */
final class OrderSection
{
    private OrderSectionId $id;
    private string $title;
    private int $position;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    /** @var array<OrderLine> */
    private array $lines = [];

    private function __construct(OrderSectionId $id, string $title, int $position)
    {
        $this->validateTitle($title);

        $this->id = $id;
        $this->title = $title;
        $this->position = $position;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(OrderSectionId $id, string $title, int $position): self
    {
        return new self($id, $title, $position);
    }

    // Line management

    public function addLine(
        OrderLineId $lineId,
        string $description,
        OrderLineType $type,
        float $quantity,
        Money $unitPriceHt,
        float $taxRate,
    ): void {
        $linePosition = count($this->lines) + 1;
        $line = OrderLine::create($lineId, $description, $type, $quantity, $unitPriceHt, $taxRate, $linePosition);

        $this->lines[] = $line;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateLine(
        OrderLineId $lineId,
        string $description,
        OrderLineType $type,
        float $quantity,
        Money $unitPriceHt,
        float $taxRate,
    ): void {
        $line = $this->findLine($lineId);
        $line->update($description, $type, $quantity, $unitPriceHt, $taxRate);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function removeLine(OrderLineId $lineId): void
    {
        $index = $this->findLineIndex($lineId);
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);

        $this->reorderLines();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Update methods

    public function update(string $title): void
    {
        $this->validateTitle($title);
        $this->title = $title;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updatePosition(int $position): void
    {
        $this->position = $position;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Calculated values

    /**
     * Calculate total HT for this section (sum of all lines).
     */
    public function getTotalHt(): Money
    {
        $total = Money::zero();

        foreach ($this->lines as $line) {
            $total = $total->add($line->getTotalHt());
        }

        return $total;
    }

    /**
     * Calculate total tax amount for this section.
     */
    public function getTaxAmount(): Money
    {
        $total = Money::zero();

        foreach ($this->lines as $line) {
            $total = $total->add($line->getTaxAmount());
        }

        return $total;
    }

    /**
     * Calculate total TTC for this section (HT + tax).
     */
    public function getTotalTtc(): Money
    {
        return $this->getTotalHt()->add($this->getTaxAmount());
    }

    // Helper methods

    private function findLine(OrderLineId $lineId): OrderLine
    {
        foreach ($this->lines as $line) {
            if ($line->getId()->equals($lineId)) {
                return $line;
            }
        }

        throw new InvalidArgumentException(sprintf('Order line with ID %s not found in section', $lineId->getValue()));
    }

    private function findLineIndex(OrderLineId $lineId): int
    {
        foreach ($this->lines as $index => $line) {
            if ($line->getId()->equals($lineId)) {
                return $index;
            }
        }

        throw new InvalidArgumentException(sprintf('Order line with ID %s not found in section', $lineId->getValue()));
    }

    private function reorderLines(): void
    {
        $position = 1;
        foreach ($this->lines as $line) {
            $line->updatePosition($position);
            ++$position;
        }
    }

    // Validation

    private function validateTitle(string $title): void
    {
        if (trim($title) === '') {
            throw new InvalidArgumentException('Order section title cannot be empty');
        }
    }

    // Getters

    public function getId(): OrderSectionId
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @return array<OrderLine>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getLineCount(): int
    {
        return count($this->lines);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

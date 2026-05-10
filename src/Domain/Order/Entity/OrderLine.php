<?php

declare(strict_types=1);

namespace App\Domain\Order\Entity;

use App\Domain\Order\ValueObject\OrderLineId;
use App\Domain\Order\ValueObject\OrderLineType;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Order line entity (child of OrderSection).
 *
 * Represents a single line item on an order with quantity, unit price, type, and tax calculations.
 */
final class OrderLine
{
    private string $description;
    private float $quantity;
    private float $taxRate;
    private ?string $unit;
    private readonly DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    private function __construct(
        private readonly OrderLineId $id,
        string $description,
        private OrderLineType $type,
        float $quantity,
        private Money $unitPriceHt,
        float $taxRate,
        private int $position,
    ) {
        $this->validateDescription($description);
        $this->validateQuantity($quantity);
        $this->validateTaxRate($taxRate);
        $this->description = $description;
        $this->quantity = $quantity;
        $this->taxRate = $taxRate;
        $this->unit = null;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(
        OrderLineId $id,
        string $description,
        OrderLineType $type,
        float $quantity,
        Money $unitPriceHt,
        float $taxRate,
        int $position,
    ): self {
        return new self($id, $description, $type, $quantity, $unitPriceHt, $taxRate, $position);
    }

    public function update(
        string $description,
        OrderLineType $type,
        float $quantity,
        Money $unitPriceHt,
        float $taxRate,
    ): void {
        $this->validateDescription($description);
        $this->validateQuantity($quantity);
        $this->validateTaxRate($taxRate);

        $this->description = $description;
        $this->type = $type;
        $this->quantity = $quantity;
        $this->unitPriceHt = $unitPriceHt;
        $this->taxRate = $taxRate;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updatePosition(int $position): void
    {
        $this->position = $position;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Calculated values

    /**
     * Calculate total HT for this line (quantity × unit price).
     */
    public function getTotalHt(): Money
    {
        return $this->unitPriceHt->multiply($this->quantity);
    }

    /**
     * Calculate tax amount for this line.
     */
    public function getTaxAmount(): Money
    {
        return $this->getTotalHt()->multiply($this->taxRate);
    }

    /**
     * Calculate total TTC for this line (HT + tax).
     */
    public function getTotalTtc(): Money
    {
        return $this->getTotalHt()->add($this->getTaxAmount());
    }

    // Validation

    private function validateDescription(string $description): void
    {
        if (trim($description) === '') {
            throw new InvalidArgumentException('Order line description cannot be empty');
        }
    }

    private function validateQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Order line quantity must be positive');
        }
    }

    private function validateTaxRate(float $taxRate): void
    {
        if ($taxRate < 0 || $taxRate > 1) {
            throw new InvalidArgumentException('Tax rate must be between 0 and 1');
        }
    }

    // Getters

    public function getId(): OrderLineId
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): OrderLineType
    {
        return $this->type;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getUnitPriceHt(): Money
    {
        return $this->unitPriceHt;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function getPosition(): int
    {
        return $this->position;
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

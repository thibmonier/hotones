<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Entity;

use App\Domain\Invoice\ValueObject\InvoiceLineId;
use App\Domain\Invoice\ValueObject\TaxRate;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Invoice line entity (child of Invoice aggregate).
 *
 * Represents a single line item on an invoice with quantity, unit price, and tax calculations.
 */
final class InvoiceLine
{
    private InvoiceLineId $id;
    private string $description;
    private float $quantity;
    private Money $unitPriceHt;
    private TaxRate $taxRate;
    private ?string $unit;
    private int $position;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    private function __construct(
        InvoiceLineId $id,
        string $description,
        float $quantity,
        Money $unitPriceHt,
        TaxRate $taxRate,
        int $position,
    ) {
        $this->validateDescription($description);
        $this->validateQuantity($quantity);

        $this->id = $id;
        $this->description = $description;
        $this->quantity = $quantity;
        $this->unitPriceHt = $unitPriceHt;
        $this->taxRate = $taxRate;
        $this->unit = null;
        $this->position = $position;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(
        InvoiceLineId $id,
        string $description,
        float $quantity,
        Money $unitPriceHt,
        TaxRate $taxRate,
        int $position,
    ): self {
        return new self($id, $description, $quantity, $unitPriceHt, $taxRate, $position);
    }

    public function update(
        string $description,
        float $quantity,
        Money $unitPriceHt,
        TaxRate $taxRate,
    ): void {
        $this->validateDescription($description);
        $this->validateQuantity($quantity);

        $this->description = $description;
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
        return $this->taxRate->calculateTax($this->getTotalHt());
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
            throw new InvalidArgumentException('Invoice line description cannot be empty');
        }
    }

    private function validateQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Invoice line quantity must be positive');
        }
    }

    // Getters

    public function getId(): InvoiceLineId
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getUnitPriceHt(): Money
    {
        return $this->unitPriceHt;
    }

    public function getTaxRate(): TaxRate
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

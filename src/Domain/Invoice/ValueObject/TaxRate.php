<?php

declare(strict_types=1);

namespace App\Domain\Invoice\ValueObject;

use App\Domain\Shared\ValueObject\Money;
use InvalidArgumentException;

/**
 * Tax rate value object (TVA).
 *
 * Stores rate as basis points (1% = 100 basis points) for precision.
 */
final readonly class TaxRate
{
    private const int STANDARD_RATE_FR = 2000; // 20.00%
    private const int REDUCED_RATE_FR = 1000; // 10.00%
    private const int SUPER_REDUCED_FR = 550; // 5.50%
    private const int ZERO_RATE = 0; // 0.00%

    private function __construct(
        private int $basisPoints,
    ) {
        if ($basisPoints < 0 || $basisPoints > 10000) {
            throw new InvalidArgumentException(sprintf('Tax rate must be between 0%% and 100%%, got: %.2f%%', $basisPoints / 100));
        }
    }

    /**
     * Create from percentage (e.g., 20.0 for 20%).
     */
    public static function fromPercentage(float $percentage): self
    {
        return new self((int) round($percentage * 100));
    }

    /**
     * Create from basis points (e.g., 2000 for 20%).
     */
    public static function fromBasisPoints(int $basisPoints): self
    {
        return new self($basisPoints);
    }

    /**
     * French standard VAT rate (20%).
     */
    public static function standardFrance(): self
    {
        return new self(self::STANDARD_RATE_FR);
    }

    /**
     * French reduced VAT rate (10%).
     */
    public static function reducedFrance(): self
    {
        return new self(self::REDUCED_RATE_FR);
    }

    /**
     * French super-reduced VAT rate (5.5%).
     */
    public static function superReducedFrance(): self
    {
        return new self(self::SUPER_REDUCED_FR);
    }

    /**
     * Zero VAT rate (export, exempt).
     */
    public static function zero(): self
    {
        return new self(self::ZERO_RATE);
    }

    public function getBasisPoints(): int
    {
        return $this->basisPoints;
    }

    public function getPercentage(): float
    {
        return $this->basisPoints / 100;
    }

    public function getMultiplier(): float
    {
        return $this->basisPoints / 10000;
    }

    /**
     * Calculate tax amount from a pre-tax amount.
     */
    public function calculateTax(Money $amountHt): Money
    {
        return $amountHt->multiply($this->getMultiplier());
    }

    /**
     * Calculate total (TTC) from a pre-tax amount (HT).
     */
    public function calculateTotalWithTax(Money $amountHt): Money
    {
        return $amountHt->add($this->calculateTax($amountHt));
    }

    public function isZero(): bool
    {
        return $this->basisPoints === 0;
    }

    public function equals(self $other): bool
    {
        return $this->basisPoints === $other->basisPoints;
    }

    public function format(): string
    {
        return sprintf('%.2f%%', $this->getPercentage());
    }

    public function __toString(): string
    {
        return $this->format();
    }
}

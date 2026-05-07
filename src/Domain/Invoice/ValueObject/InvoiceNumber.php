<?php

declare(strict_types=1);

namespace App\Domain\Invoice\ValueObject;

use InvalidArgumentException;

/**
 * Invoice number value object.
 *
 * Format: F[YYYY][MM][NNN] (e.g., F202501001)
 * - F: Fixed prefix for invoices
 * - YYYY: Year (4 digits)
 * - MM: Month (2 digits)
 * - NNN: Sequential number (3+ digits, zero-padded)
 */
final readonly class InvoiceNumber
{
    private const string PREFIX = 'F';
    private const string PATTERN = '/^F\d{4}\d{2}\d{3,}$/';

    private function __construct(
        private string $value,
    ) {
        if (!$this->isValidFormat($value)) {
            throw new InvalidArgumentException(sprintf('Invalid invoice number format: %s. Expected format: F[YYYY][MM][NNN]', $value));
        }
    }

    public static function fromString(string $number): self
    {
        return new self($number);
    }

    public static function generate(int $year, int $month, int $sequence): self
    {
        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException(sprintf('Year must be between 2000 and 2100, got: %d', $year));
        }

        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException(sprintf('Month must be between 1 and 12, got: %d', $month));
        }

        if ($sequence < 1) {
            throw new InvalidArgumentException(sprintf('Sequence must be positive, got: %d', $sequence));
        }

        $number = sprintf('%s%04d%02d%03d', self::PREFIX, $year, $month, $sequence);

        return new self($number);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getYear(): int
    {
        return (int) substr($this->value, 1, 4);
    }

    public function getMonth(): int
    {
        return (int) substr($this->value, 5, 2);
    }

    public function getSequence(): int
    {
        return (int) substr($this->value, 7);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function isValidFormat(string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 1;
    }
}

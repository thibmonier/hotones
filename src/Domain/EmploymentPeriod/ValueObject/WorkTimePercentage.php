<?php

declare(strict_types=1);

namespace App\Domain\EmploymentPeriod\ValueObject;

use InvalidArgumentException;

final readonly class WorkTimePercentage
{
    private const float MIN_PERCENT = 0.0;
    private const float MAX_PERCENT = 100.0;
    private const int DECIMAL_PRECISION = 2;

    private function __construct(
        private float $value,
    ) {
        if ($value <= self::MIN_PERCENT) {
            throw new InvalidArgumentException(sprintf('WorkTimePercentage must be strictly positive, got %.2f', $value));
        }

        if ($value > self::MAX_PERCENT) {
            throw new InvalidArgumentException(sprintf('WorkTimePercentage cannot exceed %.0f%%, got %.2f', self::MAX_PERCENT, $value));
        }
    }

    public static function fromFloat(float $value): self
    {
        return new self(round($value, self::DECIMAL_PRECISION));
    }

    public static function fromDecimalString(string $value): self
    {
        return self::fromFloat((float) $value);
    }

    public static function fullTime(): self
    {
        return new self(self::MAX_PERCENT);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function asRatio(): float
    {
        return $this->value / 100.0;
    }

    public function isFullTime(): bool
    {
        return abs($this->value - self::MAX_PERCENT) < 0.001;
    }

    public function equals(self $other): bool
    {
        return abs($this->value - $other->value) < 0.001;
    }

    public function __toString(): string
    {
        return number_format($this->value, self::DECIMAL_PRECISION, '.', '');
    }
}

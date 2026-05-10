<?php

declare(strict_types=1);

namespace App\Domain\EmploymentPeriod\ValueObject;

use InvalidArgumentException;

final readonly class WeeklyHours
{
    private const float MIN_HOURS = 0.0;
    private const float MAX_HOURS = 80.0;
    private const int DECIMAL_PRECISION = 2;

    private function __construct(
        private float $value,
    ) {
        if ($value <= self::MIN_HOURS) {
            throw new InvalidArgumentException(sprintf('WeeklyHours must be strictly positive, got %.2f', $value));
        }

        if ($value > self::MAX_HOURS) {
            throw new InvalidArgumentException(sprintf('WeeklyHours cannot exceed %.0f hours per week, got %.2f', self::MAX_HOURS, $value));
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

    public function getValue(): float
    {
        return $this->value;
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

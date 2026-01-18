<?php

declare(strict_types=1);

namespace App\Domain\Timesheet\ValueObject;

/**
 * Hours value object representing time duration.
 */
final readonly class Hours
{
    private const float MAX_HOURS_PER_DAY = 24.0;
    private const float MIN_HOURS = 0.0;

    private function __construct(
        private float $value,
    ) {
        if ($value < self::MIN_HOURS) {
            throw new \InvalidArgumentException('Hours cannot be negative.');
        }

        if ($value > self::MAX_HOURS_PER_DAY) {
            throw new \InvalidArgumentException(
                sprintf('Hours cannot exceed %s per entry.', self::MAX_HOURS_PER_DAY)
            );
        }
    }

    public static function fromFloat(float $value): self
    {
        return new self($value);
    }

    public static function fromString(string $value): self
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid hours format: %s', $value)
            );
        }

        return new self((float) $value);
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function isZero(): bool
    {
        return $this->value === 0.0;
    }

    public function isPositive(): bool
    {
        return $this->value > 0.0;
    }

    public function equals(self $other): bool
    {
        return abs($this->value - $other->value) < 0.0001;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Vacation\ValueObject;

use InvalidArgumentException;

final readonly class DailyHours
{
    private const string DEFAULT = '8.00';
    private const float MIN      = 0.0;
    private const float MAX      = 8.0;

    private function __construct(
        private string $value,
    ) {
        $floatValue = (float) $value;
        if ($floatValue < self::MIN || $floatValue > self::MAX) {
            throw new InvalidArgumentException(sprintf('Daily hours must be between %.1f and %.1f, got %s', self::MIN, self::MAX, $value));
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function fullDay(): self
    {
        return new self(self::DEFAULT);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toFloat(): float
    {
        return (float) $this->value;
    }

    public function calculateTotalHours(int $numberOfDays): string
    {
        return bcmul((string) $numberOfDays, $this->value, 2);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

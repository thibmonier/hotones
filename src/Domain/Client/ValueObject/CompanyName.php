<?php

declare(strict_types=1);

namespace App\Domain\Client\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class CompanyName implements Stringable
{
    private const int MIN_LENGTH = 2;
    private const int MAX_LENGTH = 255;

    private function __construct(
        private string $value,
    ) {
        $trimmed = trim($value);

        if (strlen($trimmed) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(sprintf('Company name must be at least %d characters', self::MIN_LENGTH));
        }

        if (strlen($trimmed) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('Company name cannot exceed %d characters', self::MAX_LENGTH));
        }
    }

    public static function fromString(string $name): self
    {
        return new self(trim($name));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Timesheet\ValueObject;

use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Timesheet identity value object.
 */
final readonly class TimesheetId
{
    private function __construct(
        private string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException(sprintf('Invalid TimesheetId format: %s', $value));
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
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

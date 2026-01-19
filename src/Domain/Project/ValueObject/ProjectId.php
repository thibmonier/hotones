<?php

declare(strict_types=1);

namespace App\Domain\Project\ValueObject;

use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Project identifier value object.
 */
final readonly class ProjectId
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException(sprintf('Invalid project ID format: %s', $value));
        }

        return new self($value);
    }

    public function value(): string
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

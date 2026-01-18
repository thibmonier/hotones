<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

use Symfony\Component\Uid\Uuid;

final readonly class OrderId
{
    private function __construct(
        private string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid UUID format for OrderId: %s', $value)
            );
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }

    public static function fromString(string $id): self
    {
        return new self($id);
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

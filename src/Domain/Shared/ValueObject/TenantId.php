<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Tenant identifier — wraps Company id (integer auto-increment in current schema).
 *
 * Domain pure: no Doctrine, no Symfony. Compatible with `.claude/rules/04-value-objects.md`
 * (final readonly + validation in constructor + equals + factory methods).
 *
 * Future-proof: when migrating Company id to UUID, swap the int payload for a string
 * payload without changing consumers. The factory methods absorb the change.
 */
final readonly class TenantId implements Stringable
{
    private function __construct(
        public int $value,
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException(sprintf('TenantId must be a positive integer, got %d', $value));
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public static function fromString(string $value): self
    {
        if (!ctype_digit($value)) {
            throw new InvalidArgumentException(sprintf('TenantId string must be a positive integer literal, got "%s"', $value));
        }

        return new self((int) $value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}

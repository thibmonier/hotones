<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\ValueObject;

use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * WorkItem identifier (UUID native or legacy:N int wrapper for Phase 2 ACL).
 *
 * EPIC-003 Phase 1 (sprint-019 US-097).
 *
 * @see ADR-0008 ACL pattern
 * @see ADR-0013 EPIC-003 scope WorkItem & Profitability
 */
final readonly class WorkItemId
{
    private const string LEGACY_PREFIX = 'legacy:';

    private function __construct(
        private string $value,
    ) {
        if (str_starts_with($value, self::LEGACY_PREFIX)) {
            return;
        }

        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException(sprintf('Invalid WorkItemId format: %s', $value));
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

    public static function fromLegacyInt(int $id): self
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Legacy id must be positive');
        }

        return new self(self::LEGACY_PREFIX.$id);
    }

    public function isLegacy(): bool
    {
        return str_starts_with($this->value, self::LEGACY_PREFIX);
    }

    public function toLegacyInt(): int
    {
        if (!$this->isLegacy()) {
            throw new InvalidArgumentException('WorkItemId is not a legacy int wrapper');
        }

        return (int) substr($this->value, strlen(self::LEGACY_PREFIX));
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

<?php

declare(strict_types=1);

namespace App\Domain\Project\ValueObject;

use InvalidArgumentException;
use Stringable;
use Symfony\Component\Uid\Uuid;

/**
 * ProjectTask identifier (UUID native or `legacy:N` int wrapper for Phase 2 ACL).
 *
 * Sprint-020 EPIC-003 Phase 2 (US-098) — utilisé par WorkItem aggregate
 * pour rattachement optionnel à une tâche projet (cf ADR-0015 décision Q1 :
 * `taskId` nullable, allocation fictive niveau projet si null).
 *
 * @see ADR-0008 ACL pattern
 * @see ADR-0015 EPIC-003 Phase 2 décisions
 */
final readonly class ProjectTaskId implements Stringable
{
    private const string LEGACY_PREFIX = 'legacy:';

    private function __construct(
        private string $value,
    ) {
        if (str_starts_with($value, self::LEGACY_PREFIX)) {
            return;
        }

        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException(sprintf('Invalid ProjectTaskId format: %s', $value));
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
            throw new InvalidArgumentException('ProjectTaskId is not a legacy int wrapper');
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

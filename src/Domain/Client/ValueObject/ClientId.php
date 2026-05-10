<?php

declare(strict_types=1);

namespace App\Domain\Client\ValueObject;

use InvalidArgumentException;
use Stringable;
use Symfony\Component\Uid\Uuid;

final readonly class ClientId implements Stringable
{
    /** Legacy int-id prefix used during EPIC-001 Phase 2 strangler fig. */
    private const string LEGACY_PREFIX = 'legacy:';

    private function __construct(
        private string $value,
    ) {
        if (str_starts_with($value, self::LEGACY_PREFIX)) {
            // Legacy int wrapped as `legacy:NNN` during ACL bridge phase.
            return;
        }

        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException(sprintf('Invalid ClientId format: %s', $value));
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

    /**
     * Wrap a legacy auto-increment int id as a ClientId during the EPIC-001
     * Phase 2 strangler fig. Once the legacy table is decommissioned, all
     * Client ids will be true UUIDs and this factory can be removed.
     */
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
            throw new InvalidArgumentException('ClientId is not a legacy int wrapper');
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

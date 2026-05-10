<?php

declare(strict_types=1);

namespace App\Domain\Company\ValueObject;

use InvalidArgumentException;
use Stringable;
use Symfony\Component\Uid\Uuid;

/**
 * Value object representing a Company's unique identifier.
 */
final readonly class CompanyId implements Stringable
{
    private const string LEGACY_PREFIX = 'legacy:';

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
        if (str_starts_with($value, self::LEGACY_PREFIX)) {
            return new self($value);
        }

        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException(sprintf('Invalid Company ID format: %s', $value));
        }

        return new self($value);
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
            throw new InvalidArgumentException('CompanyId is not a legacy int wrapper');
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

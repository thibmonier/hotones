<?php

declare(strict_types=1);

namespace App\Domain\Company\ValueObject;

/**
 * Value object representing a Company's URL slug.
 */
final readonly class CompanySlug
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 63;
    private const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        self::validate($normalized);

        return new self($normalized);
    }

    private static function validate(string $value): void
    {
        if (strlen($value) < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Company slug must be at least %d characters', self::MIN_LENGTH)
            );
        }

        if (strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Company slug must not exceed %d characters', self::MAX_LENGTH)
            );
        }

        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new \InvalidArgumentException(
                'Company slug must contain only lowercase letters, numbers, and hyphens'
            );
        }
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

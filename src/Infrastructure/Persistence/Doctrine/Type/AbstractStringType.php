<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Abstract Doctrine type for string-based Value Objects.
 *
 * Value Objects must implement:
 * - static fromString(string $value): self
 * - getValue(): string
 *
 * @template T of object
 */
abstract class AbstractStringType extends Type
{
    /**
     * @return class-string<T>
     */
    abstract protected function getValueObjectClass(): string;

    abstract public function getName(): string;

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    /**
     * @return T|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $class = $this->getValueObjectClass();

        return $class::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        return $value->getValue();
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}

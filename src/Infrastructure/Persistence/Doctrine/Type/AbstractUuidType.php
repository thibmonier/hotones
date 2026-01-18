<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Abstract base class for UUID-based Value Object Doctrine types.
 *
 * This class provides a common implementation for all UUID value object types,
 * reducing code duplication and ensuring consistent behavior.
 */
abstract class AbstractUuidType extends Type
{
    /**
     * Returns the fully qualified class name of the Value Object.
     */
    abstract protected function getValueObjectClass(): string;

    /**
     * Returns the type name used in Doctrine mappings.
     */
    abstract public function getName(): string;

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getGuidTypeDeclarationSQL($column);
    }

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

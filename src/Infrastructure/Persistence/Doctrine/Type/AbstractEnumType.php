<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;

/**
 * Abstract base class for PHP 8.1+ backed enum Doctrine types.
 *
 * This class provides a common implementation for all backed enum types,
 * handling conversion between PHP enum instances and database string values.
 *
 * @template T of \BackedEnum
 */
abstract class AbstractEnumType extends Type
{
    /**
     * Returns the fully qualified class name of the enum.
     *
     * @return class-string<T>
     */
    abstract protected function getEnumClass(): string;

    /**
     * Returns the type name used in Doctrine mappings.
     */
    abstract public function getName(): string;

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL([
            'length' => 50,
        ]);
    }

    /**
     * @return T|null
     */
    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $enumClass = $this->getEnumClass();

        return $enumClass::from($value);
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        return $value->value;
    }
}

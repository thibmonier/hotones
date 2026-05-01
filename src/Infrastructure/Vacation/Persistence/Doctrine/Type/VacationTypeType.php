<?php

declare(strict_types=1);

namespace App\Infrastructure\Vacation\Persistence\Doctrine\Type;

use App\Domain\Vacation\ValueObject\VacationType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class VacationTypeType extends Type
{
    public const string NAME = 'vacation_type';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?VacationType
    {
        if ($value === null) {
            return null;
        }

        return VacationType::from((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof VacationType) {
            return $value->value;
        }

        return (string) $value;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

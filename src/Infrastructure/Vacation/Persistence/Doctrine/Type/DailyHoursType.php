<?php

declare(strict_types=1);

namespace App\Infrastructure\Vacation\Persistence\Doctrine\Type;

use App\Domain\Vacation\ValueObject\DailyHours;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class DailyHoursType extends Type
{
    public const string NAME = 'daily_hours';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DailyHours
    {
        if ($value === null) {
            return null;
        }

        return DailyHours::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DailyHours) {
            return $value->getValue();
        }

        return (string) $value;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDecimalTypeDeclarationSQL($column);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

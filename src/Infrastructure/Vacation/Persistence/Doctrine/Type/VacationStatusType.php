<?php

declare(strict_types=1);

namespace App\Infrastructure\Vacation\Persistence\Doctrine\Type;

use App\Domain\Vacation\ValueObject\VacationStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;

final class VacationStatusType extends Type
{
    public const string NAME = 'vacation_status';

    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?VacationStatus
    {
        if ($value === null) {
            return null;
        }

        return VacationStatus::from((string) $value);
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof VacationStatus) {
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

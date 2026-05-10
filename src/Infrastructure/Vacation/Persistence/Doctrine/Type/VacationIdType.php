<?php

declare(strict_types=1);

namespace App\Infrastructure\Vacation\Persistence\Doctrine\Type;

use App\Domain\Vacation\ValueObject\VacationId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;

final class VacationIdType extends Type
{
    public const string NAME = 'vacation_id';

    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?VacationId
    {
        if ($value === null) {
            return null;
        }

        return VacationId::fromString((string) $value);
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof VacationId) {
            return $value->getValue();
        }

        return (string) $value;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getGuidTypeDeclarationSQL($column);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

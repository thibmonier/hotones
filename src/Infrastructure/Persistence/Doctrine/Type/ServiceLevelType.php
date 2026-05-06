<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Client\ValueObject\ServiceLevel;

/**
 * Doctrine type for ServiceLevel enum.
 *
 * @extends AbstractEnumType<ServiceLevel>
 */
final class ServiceLevelType extends AbstractEnumType
{
    public const string NAME = 'service_level';

    protected function getEnumClass(): string
    {
        return ServiceLevel::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

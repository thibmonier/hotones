<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Client\ValueObject\ClientId;

/**
 * Doctrine type for ClientId value object.
 */
final class ClientIdType extends AbstractUuidType
{
    public const string NAME = 'client_id';

    protected function getValueObjectClass(): string
    {
        return ClientId::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Order\ValueObject\OrderId;

/**
 * Doctrine type for OrderId value object.
 */
final class OrderIdType extends AbstractUuidType
{
    public const string NAME = 'order_id';

    protected function getValueObjectClass(): string
    {
        return OrderId::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

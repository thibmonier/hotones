<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Order\ValueObject\OrderLineId;

/**
 * Doctrine type for OrderLineId value object.
 */
final class OrderLineIdType extends AbstractUuidType
{
    public const string NAME = 'order_line_id';

    protected function getValueObjectClass(): string
    {
        return OrderLineId::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

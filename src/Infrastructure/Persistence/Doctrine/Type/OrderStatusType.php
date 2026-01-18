<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Order\ValueObject\OrderStatus;

/**
 * Doctrine type for OrderStatus enum.
 *
 * @extends AbstractEnumType<OrderStatus>
 */
final class OrderStatusType extends AbstractEnumType
{
    public const string NAME = 'order_status';

    protected function getEnumClass(): string
    {
        return OrderStatus::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

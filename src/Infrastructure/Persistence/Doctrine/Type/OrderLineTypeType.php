<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Order\ValueObject\OrderLineType;

/**
 * Doctrine type for OrderLineType enum.
 *
 * @extends AbstractEnumType<OrderLineType>
 */
final class OrderLineTypeType extends AbstractEnumType
{
    public const string NAME = 'order_line_type';

    protected function getEnumClass(): string
    {
        return OrderLineType::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

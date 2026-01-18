<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Order\ValueObject\OrderSectionId;

/**
 * Doctrine type for OrderSectionId value object.
 */
final class OrderSectionIdType extends AbstractUuidType
{
    public const string NAME = 'order_section_id';

    protected function getValueObjectClass(): string
    {
        return OrderSectionId::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

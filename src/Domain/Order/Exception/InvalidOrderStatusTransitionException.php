<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Shared\Exception\DomainException;

final class InvalidOrderStatusTransitionException extends DomainException
{
    public static function create(OrderStatus $from, OrderStatus $to): self
    {
        return new self(
            sprintf(
                'Cannot transition order status from "%s" to "%s"',
                $from->value,
                $to->value
            )
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Shared\Exception\DomainException;

final class OrderNotFoundException extends DomainException
{
    public static function withId(OrderId $id): self
    {
        return new self(
            sprintf('Order with ID "%s" was not found', $id->getValue())
        );
    }
}

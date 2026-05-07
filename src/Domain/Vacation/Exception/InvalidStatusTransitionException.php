<?php

declare(strict_types=1);

namespace App\Domain\Vacation\Exception;

use App\Domain\Vacation\ValueObject\VacationStatus;
use DomainException;

final class InvalidStatusTransitionException extends DomainException
{
    public static function create(VacationStatus $from, VacationStatus $to): self
    {
        return new self(sprintf('Cannot transition vacation from "%s" to "%s"', $from->value, $to->value));
    }
}

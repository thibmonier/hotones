<?php

declare(strict_types=1);

namespace App\Domain\Vacation\Exception;

use DomainException;

final class InvalidVacationException extends DomainException
{
    public static function endDateBeforeStartDate(): self
    {
        return new self('End date must be after or equal to start date');
    }
}

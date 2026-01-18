<?php

declare(strict_types=1);

namespace App\Domain\Timesheet\Exception;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Timesheet\ValueObject\TimesheetId;

/**
 * Exception thrown when a timesheet entry cannot be found.
 */
final class TimesheetNotFoundException extends DomainException
{
    public static function withId(TimesheetId $timesheetId): self
    {
        return new self(
            sprintf('Timesheet entry with ID "%s" was not found.', $timesheetId->getValue())
        );
    }
}

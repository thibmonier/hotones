<?php

declare(strict_types=1);

namespace App\Domain\Timesheet\Exception;

use App\Domain\Shared\Exception\DomainException;
use DateTimeImmutable;

/**
 * Exception thrown when timesheet validation fails.
 */
final class InvalidTimesheetException extends DomainException
{
    public static function zeroHours(): self
    {
        return new self('Timesheet entry must have hours greater than zero.');
    }

    public static function futureDateNotAllowed(): self
    {
        return new self('Timesheet entries cannot be logged for future dates.');
    }

    public static function duplicateEntry(DateTimeImmutable $date, string $projectId): self
    {
        return new self(
            sprintf(
                'A timesheet entry already exists for date "%s" on project "%s".',
                $date->format('Y-m-d'),
                $projectId,
            ),
        );
    }

    public static function invalidDateRange(): self
    {
        return new self('The specified date range is invalid.');
    }
}

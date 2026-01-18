<?php

declare(strict_types=1);

namespace App\Domain\Timesheet\Event;

use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\Timesheet\ValueObject\TimesheetId;

/**
 * Domain event raised when a timesheet entry is deleted.
 */
final readonly class TimesheetDeletedEvent implements DomainEventInterface
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private TimesheetId $timesheetId,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public static function create(TimesheetId $timesheetId): self
    {
        return new self($timesheetId);
    }

    public function getTimesheetId(): TimesheetId
    {
        return $this->timesheetId;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

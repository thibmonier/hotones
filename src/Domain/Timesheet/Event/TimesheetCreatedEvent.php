<?php

declare(strict_types=1);

namespace App\Domain\Timesheet\Event;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\Timesheet\ValueObject\TimesheetId;
use DateTimeImmutable;

/**
 * Domain event raised when a timesheet entry is created.
 */
final readonly class TimesheetCreatedEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        private TimesheetId $timesheetId,
        private CompanyId $companyId,
        private ContributorId $contributorId,
        private ProjectId $projectId,
        private DateTimeImmutable $date,
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public static function create(
        TimesheetId $timesheetId,
        CompanyId $companyId,
        ContributorId $contributorId,
        ProjectId $projectId,
        DateTimeImmutable $date,
    ): self {
        return new self($timesheetId, $companyId, $contributorId, $projectId, $date);
    }

    public function getTimesheetId(): TimesheetId
    {
        return $this->timesheetId;
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getContributorId(): ContributorId
    {
        return $this->contributorId;
    }

    public function getProjectId(): ProjectId
    {
        return $this->projectId;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

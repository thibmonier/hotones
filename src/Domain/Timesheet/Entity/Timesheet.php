<?php

declare(strict_types=1);

namespace App\Domain\Timesheet\Entity;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Trait\RecordsDomainEvents;
use App\Domain\Timesheet\Event\TimesheetCreatedEvent;
use App\Domain\Timesheet\Event\TimesheetDeletedEvent;
use App\Domain\Timesheet\Event\TimesheetUpdatedEvent;
use App\Domain\Timesheet\Exception\InvalidTimesheetException;
use App\Domain\Timesheet\ValueObject\Hours;
use App\Domain\Timesheet\ValueObject\TimesheetId;

/**
 * Timesheet aggregate root - represents a time entry logged by a contributor.
 *
 * Timesheets track the hours worked by contributors on specific projects,
 * optionally linked to tasks and subtasks for detailed time tracking.
 */
final class Timesheet implements AggregateRootInterface
{
    use RecordsDomainEvents;

    private TimesheetId $id;
    private CompanyId $companyId;
    private ContributorId $contributorId;
    private ProjectId $projectId;

    // Optional task/subtask references (stored as string IDs for cross-context reference)
    private ?string $taskId;
    private ?string $subTaskId;

    private \DateTimeImmutable $date;
    private Hours $hours;
    private ?string $notes;

    // Timestamps
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct(
        TimesheetId $id,
        CompanyId $companyId,
        ContributorId $contributorId,
        ProjectId $projectId,
        \DateTimeImmutable $date,
        Hours $hours,
    ) {
        $this->id = $id;
        $this->companyId = $companyId;
        $this->contributorId = $contributorId;
        $this->projectId = $projectId;
        $this->date = $date;
        $this->hours = $hours;

        // Default values
        $this->taskId = null;
        $this->subTaskId = null;
        $this->notes = null;

        // Timestamps
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(
        TimesheetId $id,
        CompanyId $companyId,
        ContributorId $contributorId,
        ProjectId $projectId,
        \DateTimeImmutable $date,
        Hours $hours,
        ?string $taskId = null,
        ?string $subTaskId = null,
        ?string $notes = null,
    ): self {
        // Validate hours
        if ($hours->isZero()) {
            throw InvalidTimesheetException::zeroHours();
        }

        $timesheet = new self($id, $companyId, $contributorId, $projectId, $date, $hours);
        $timesheet->taskId = $taskId;
        $timesheet->subTaskId = $subTaskId;
        $timesheet->notes = $notes;

        $timesheet->recordEvent(
            TimesheetCreatedEvent::create($id, $companyId, $contributorId, $projectId, $date)
        );

        return $timesheet;
    }

    /**
     * Update time entry details.
     */
    public function update(
        Hours $hours,
        ?string $taskId = null,
        ?string $subTaskId = null,
        ?string $notes = null,
    ): void {
        if ($hours->isZero()) {
            throw InvalidTimesheetException::zeroHours();
        }

        $this->hours = $hours;
        $this->taskId = $taskId;
        $this->subTaskId = $subTaskId;
        $this->notes = $notes;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(TimesheetUpdatedEvent::create($this->id));
    }

    /**
     * Update hours only.
     */
    public function updateHours(Hours $hours): void
    {
        if ($hours->isZero()) {
            throw InvalidTimesheetException::zeroHours();
        }

        $this->hours = $hours;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(TimesheetUpdatedEvent::create($this->id));
    }

    /**
     * Update notes only.
     */
    public function updateNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(TimesheetUpdatedEvent::create($this->id));
    }

    /**
     * Assign to a task.
     */
    public function assignToTask(?string $taskId, ?string $subTaskId = null): void
    {
        $this->taskId = $taskId;
        $this->subTaskId = $subTaskId;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(TimesheetUpdatedEvent::create($this->id));
    }

    /**
     * Mark for deletion (records event before actual deletion).
     */
    public function markForDeletion(): void
    {
        $this->recordEvent(TimesheetDeletedEvent::create($this->id));
    }

    // Calculated values

    public function hasTask(): bool
    {
        return $this->taskId !== null;
    }

    public function hasSubTask(): bool
    {
        return $this->subTaskId !== null;
    }

    public function hasNotes(): bool
    {
        return $this->notes !== null && $this->notes !== '';
    }

    // Getters

    public function getId(): TimesheetId
    {
        return $this->id;
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

    public function getTaskId(): ?string
    {
        return $this->taskId;
    }

    public function getSubTaskId(): ?string
    {
        return $this->subTaskId;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getHours(): Hours
    {
        return $this->hours;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

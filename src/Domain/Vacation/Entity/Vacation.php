<?php

declare(strict_types=1);

namespace App\Domain\Vacation\Entity;

use App\Domain\Vacation\Event\VacationApproved;
use App\Domain\Vacation\Event\VacationCancelled;
use App\Domain\Vacation\Event\VacationRejected;
use App\Domain\Vacation\Event\VacationRequested;
use App\Domain\Vacation\ValueObject\DailyHours;
use App\Domain\Vacation\ValueObject\DateRange;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Domain\Vacation\ValueObject\VacationStatus;
use App\Domain\Vacation\ValueObject\VacationType;
use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\Interface\CompanyOwnedInterface;
use App\Entity\User;
use DateTimeImmutable;

final class Vacation implements CompanyOwnedInterface
{
    /** @var list<object> */
    private array $domainEvents = [];

    private VacationId $id;
    private Company $company;
    private Contributor $contributor;
    private DateRange $dateRange;
    private VacationType $type;
    private VacationStatus $status;
    private DailyHours $dailyHours;
    private ?string $reason;
    private ?string $rejectionReason = null;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $approvedAt;
    private ?User $approvedBy;

    private function __construct(
        VacationId $id,
        Company $company,
        Contributor $contributor,
        DateRange $dateRange,
        VacationType $type,
        DailyHours $dailyHours,
        ?string $reason,
    ) {
        $this->id          = $id;
        $this->company     = $company;
        $this->contributor = $contributor;
        $this->dateRange   = $dateRange;
        $this->type        = $type;
        $this->dailyHours  = $dailyHours;
        $this->reason      = $reason;
        $this->status      = VacationStatus::PENDING;
        $this->createdAt   = new DateTimeImmutable();
        $this->approvedAt  = null;
        $this->approvedBy  = null;
    }

    public static function request(
        VacationId $id,
        Company $company,
        Contributor $contributor,
        DateRange $dateRange,
        VacationType $type,
        DailyHours $dailyHours,
        ?string $reason = null,
    ): self {
        $vacation = new self($id, $company, $contributor, $dateRange, $type, $dailyHours, $reason);

        $vacation->recordEvent(new VacationRequested($id));

        return $vacation;
    }

    public function approve(User $approvedBy): void
    {
        $this->status     = $this->status->transitionTo(VacationStatus::APPROVED);
        $this->approvedAt = new DateTimeImmutable();
        $this->approvedBy = $approvedBy;

        $this->recordEvent(new VacationApproved($this->id, $approvedBy->getId()));
    }

    public function reject(?string $rejectionReason = null): void
    {
        $this->status          = $this->status->transitionTo(VacationStatus::REJECTED);
        $this->rejectionReason = $rejectionReason;

        $this->recordEvent(new VacationRejected($this->id));
    }

    public function cancel(): void
    {
        $this->status = $this->status->transitionTo(VacationStatus::CANCELLED);

        $this->recordEvent(new VacationCancelled($this->id));
    }

    // --- Computed ---

    public function getTotalHours(): string
    {
        return $this->dailyHours->calculateTotalHours($this->dateRange->getNumberOfDays());
    }

    public function getNumberOfWorkingDays(): int
    {
        return $this->dateRange->getNumberOfWorkingDays();
    }

    public function getTypeLabel(): string
    {
        return $this->type->label();
    }

    // --- Getters ---

    public function getId(): VacationId
    {
        return $this->id;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getContributor(): Contributor
    {
        return $this->contributor;
    }

    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->dateRange->getStartDate();
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->dateRange->getEndDate();
    }

    public function getType(): VacationType
    {
        return $this->type;
    }

    public function getStatus(): VacationStatus
    {
        return $this->status;
    }

    public function getDailyHours(): DailyHours
    {
        return $this->dailyHours;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getApprovedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    // --- Domain Events ---

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return list<object>
     */
    public function pullDomainEvents(): array
    {
        $events             = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}

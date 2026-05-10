<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Entity;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Invoice\ValueObject\InvoiceId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectTaskId;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Trait\RecordsDomainEvents;
use App\Domain\Shared\ValueObject\Money;
use App\Domain\WorkItem\Event\WorkItemBilledEvent;
use App\Domain\WorkItem\Event\WorkItemPaidEvent;
use App\Domain\WorkItem\Event\WorkItemRecordedEvent;
use App\Domain\WorkItem\Event\WorkItemRevisedEvent;
use App\Domain\WorkItem\Event\WorkItemValidatedEvent;
use App\Domain\WorkItem\Exception\WorkItemInvalidTransitionException;
use App\Domain\WorkItem\ValueObject\HourlyRate;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use App\Domain\WorkItem\ValueObject\WorkItemStatus;
use DateTimeImmutable;

/**
 * WorkItem aggregate root — heures travaillées par un contributeur sur un
 * projet (avec optionnellement une tâche), avec rates de coût et facturation
 * **figés à la création** (snapshot).
 *
 * EPIC-003 Phase 1 (sprint-019 US-097).
 *
 * Mitige les risks Q3 + Q4 audit :
 * - `costRate` / `billedRate` non-null par construction (HourlyRate VO)
 * - rates immuables après création (snapshot moment T) — recalcul historique
 *   reste cohérent même si CJM/TJM contributor changent ultérieurement
 *
 * Phase 1 ne supporte PAS les tâches (`?ProjectTaskId`) ni la suppression —
 * uniquement record + revise hours (sprint-022 ajoutera linkToTask).
 *
 * @see ADR-0013 EPIC-003 scope WorkItem & Profitability
 * @see docs/02-architecture/epic-003-audit-existing-data.md
 */
final class WorkItem implements AggregateRootInterface
{
    use RecordsDomainEvents;
    private ?string $notes;
    private WorkItemStatus $status;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    private function __construct(
        private WorkItemId $id,
        private ProjectId $projectId,
        private ContributorId $contributorId,
        private DateTimeImmutable $workedOn,
        private WorkedHours $hours,
        private HourlyRate $costRate,
        private HourlyRate $billedRate,
        private ?ProjectTaskId $taskId = null,
    ) {
        $this->notes = null;
        $this->status = WorkItemStatus::DRAFT;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
    }

    /**
     * `taskId` est nullable (cf ADR-0015 décision Q1 — allocation fictive niveau projet
     * si task = NULL côté flat Timesheet).
     */
    public static function create(
        WorkItemId $id,
        ProjectId $projectId,
        ContributorId $contributorId,
        DateTimeImmutable $workedOn,
        WorkedHours $hours,
        HourlyRate $costRate,
        HourlyRate $billedRate,
        ?ProjectTaskId $taskId = null,
    ): self {
        $workItem = new self(
            $id,
            $projectId,
            $contributorId,
            $workedOn,
            $hours,
            $costRate,
            $billedRate,
            $taskId,
        );

        $workItem->recordEvent(
            WorkItemRecordedEvent::create($id, $projectId, $contributorId, $workedOn),
        );

        return $workItem;
    }

    /**
     * Reconstitute depuis stockage persistant (ACL Phase 2). N'enregistre PAS
     * d'event domain.
     *
     * @param array{taskId?: ?ProjectTaskId, notes?: ?string, status?: ?WorkItemStatus, createdAt?: ?DateTimeImmutable, updatedAt?: ?DateTimeImmutable} $extra
     */
    public static function reconstitute(
        WorkItemId $id,
        ProjectId $projectId,
        ContributorId $contributorId,
        DateTimeImmutable $workedOn,
        WorkedHours $hours,
        HourlyRate $costRate,
        HourlyRate $billedRate,
        array $extra = [],
    ): self {
        $workItem = new self(
            $id,
            $projectId,
            $contributorId,
            $workedOn,
            $hours,
            $costRate,
            $billedRate,
            $extra['taskId'] ?? null,
        );
        $workItem->notes = $extra['notes'] ?? null;
        $workItem->status = $extra['status'] ?? WorkItemStatus::DRAFT;
        $workItem->createdAt = $extra['createdAt'] ?? new DateTimeImmutable();
        $workItem->updatedAt = $extra['updatedAt'] ?? null;

        return $workItem;
    }

    // Mutations

    /**
     * Révise le nombre d'heures (correction déclaration).
     * Rates restent figés (snapshot d'origine).
     */
    public function reviseHours(WorkedHours $newHours): void
    {
        if ($this->hours->equals($newHours)) {
            return;
        }

        $oldHours = $this->hours;
        $this->hours = $newHours;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(WorkItemRevisedEvent::create($this->id, $oldHours, $newHours));
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Workflow transition validate (draft → validated).
     *
     * Idempotent : pas de re-event si déjà validated.
     *
     * @throws WorkItemInvalidTransitionException si état actuel != DRAFT et != VALIDATED
     */
    public function markAsValidated(): void
    {
        if ($this->status === WorkItemStatus::VALIDATED) {
            return;
        }

        if (!$this->status->canTransitionTo(WorkItemStatus::VALIDATED)) {
            throw new WorkItemInvalidTransitionException($this->id, $this->status, WorkItemStatus::VALIDATED);
        }

        $this->status = WorkItemStatus::VALIDATED;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(WorkItemValidatedEvent::create($this->id));
    }

    /**
     * Workflow transition bill (validated → billed). Cross-aggregate :
     * déclenché par listener `BillRelatedWorkItemsOnInvoiceCreated` consume
     * `InvoiceCreatedEvent` avec workItemIds payload (AT-3.2 ADR-0016).
     *
     * Idempotent : pas de re-event si déjà billed.
     *
     * @throws WorkItemInvalidTransitionException si état actuel != VALIDATED et != BILLED
     */
    public function markAsBilled(InvoiceId $invoiceId): void
    {
        if ($this->status === WorkItemStatus::BILLED) {
            return;
        }

        if (!$this->status->canTransitionTo(WorkItemStatus::BILLED)) {
            throw new WorkItemInvalidTransitionException($this->id, $this->status, WorkItemStatus::BILLED);
        }

        $this->status = WorkItemStatus::BILLED;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(WorkItemBilledEvent::create($this->id, $invoiceId));
    }

    /**
     * Workflow transition mark_paid (billed → paid). Cross-aggregate :
     * déclenchement automatique via `InvoicePaidEvent` listener = sprint-022+
     * (sprint-021 livre la transition Domain).
     *
     * Idempotent : pas de re-event si déjà paid.
     *
     * @throws WorkItemInvalidTransitionException si état actuel != BILLED et != PAID
     */
    public function markAsPaid(InvoiceId $invoiceId): void
    {
        if ($this->status === WorkItemStatus::PAID) {
            return;
        }

        if (!$this->status->canTransitionTo(WorkItemStatus::PAID)) {
            throw new WorkItemInvalidTransitionException($this->id, $this->status, WorkItemStatus::PAID);
        }

        $this->status = WorkItemStatus::PAID;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(WorkItemPaidEvent::create($this->id, $invoiceId));
    }

    // Calculations (pure — pas d'effet de bord, pas d'event)

    public function cost(): Money
    {
        return $this->costRate->multiply($this->hours);
    }

    public function revenue(): Money
    {
        return $this->billedRate->multiply($this->hours);
    }

    public function margin(): Money
    {
        return $this->revenue()->subtract($this->cost());
    }

    /**
     * Marge en pourcentage. Retourne 0.0 si revenue est nul.
     */
    public function marginPercent(): float
    {
        if ($this->revenue()->isZero()) {
            return 0.0;
        }

        $marginCents = $this->margin()->getAmountCents();
        $revenueCents = $this->revenue()->getAmountCents();

        return round(($marginCents / $revenueCents) * 100, 2);
    }

    // Getters

    public function getId(): WorkItemId
    {
        return $this->id;
    }

    public function getProjectId(): ProjectId
    {
        return $this->projectId;
    }

    public function getContributorId(): ContributorId
    {
        return $this->contributorId;
    }

    public function getTaskId(): ?ProjectTaskId
    {
        return $this->taskId;
    }

    public function getWorkedOn(): DateTimeImmutable
    {
        return $this->workedOn;
    }

    public function getHours(): WorkedHours
    {
        return $this->hours;
    }

    public function getCostRate(): HourlyRate
    {
        return $this->costRate;
    }

    public function getBilledRate(): HourlyRate
    {
        return $this->billedRate;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getStatus(): WorkItemStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

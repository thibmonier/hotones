<?php

declare(strict_types=1);

namespace App\Application\WorkItem\UseCase\RecordWorkItem;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectTaskId;
use App\Domain\WorkItem\Entity\WorkItem;
use App\Domain\WorkItem\Exception\DailyHoursWarningException;
use App\Domain\WorkItem\Repository\WorkItemRepositoryInterface;
use App\Domain\WorkItem\Service\DailyHoursValidator;
use App\Domain\WorkItem\ValueObject\HourlyRate;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * EPIC-003 Phase 3 (sprint-021 US-099) — UC saisie WorkItem.
 *
 * Orchestration :
 * 1. Compute additional WorkedHours
 * 2. Validate invariant journalier via DailyHoursValidator (ADR-0015)
 * 3. Si dépassement + !userOverride → DailyHoursWarningException (UI affiche
 *    warning + propose checkbox override Q2.4)
 * 4. Si dépassement + userOverride → log audit (PSR-3 + structured context)
 * 5. WorkItem::create() avec status DRAFT
 * 6. Si authorIsManager (Q3.2) → markAsValidated() → status VALIDATED
 * 7. Repository save + dispatch événements Domain
 *
 * Pas de logique role resolution ici : caller (Controller) résout
 * `authorIsManager` via Symfony Security AVANT dispatch UC.
 *
 * Pas de logique rates resolution ici : caller passe `costRateAmount` +
 * `billedRateAmount` pre-calculés (sprint-022+ : centralise via Domain
 * Service `RateResolver` si besoin).
 */
final readonly class RecordWorkItemUseCase
{
    public function __construct(
        private WorkItemRepositoryInterface $workItemRepository,
        private DailyHoursValidator $dailyHoursValidator,
        private MessageBusInterface $eventBus,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws DailyHoursWarningException si dépassement seuil journalier ET userOverride === false
     */
    public function execute(RecordWorkItemCommand $command): WorkItemId
    {
        $contributorId = ContributorId::fromLegacyInt($command->contributorIdLegacy);
        $projectId = ProjectId::fromLegacyInt($command->projectIdLegacy);
        $date = new DateTimeImmutable($command->date);
        $hours = WorkedHours::fromFloat($command->hours);
        $taskId = $command->taskIdLegacy !== null
            ? ProjectTaskId::fromLegacyInt($command->taskIdLegacy)
            : null;

        // Invariant journalier (ADR-0015 + ADR-0016 Q2.4)
        if ($this->dailyHoursValidator->isExceeded($contributorId, $date, $hours)) {
            $maxHours = $this->dailyHoursValidator->dailyMaxHours($contributorId, $date);
            $existingTotal = $this->dailyHoursValidator->currentDailyTotal($contributorId, $date);
            $newTotal = WorkedHours::fromFloat($existingTotal + $hours->getValue());

            if (!$command->userOverride) {
                throw new DailyHoursWarningException($contributorId, $date, $newTotal, $maxHours);
            }

            // Override accepté → audit log structuré (US-095 logging JSON)
            $this->logger->warning('WorkItem daily hours override accepted', [
                'contributor_id' => (string) $contributorId,
                'date' => $date->format('Y-m-d'),
                'daily_total_after_override' => $newTotal->getValue(),
                'daily_max_hours' => $maxHours->getValue(),
                'override_excess' => $newTotal->getValue() - $maxHours->getValue(),
                'project_id' => (string) $projectId,
                'comment' => $command->comment,
            ]);
        }

        $workItem = WorkItem::create(
            id: WorkItemId::generate(),
            projectId: $projectId,
            contributorId: $contributorId,
            workedOn: $date,
            hours: $hours,
            costRate: HourlyRate::fromAmount($command->costRateAmount),
            billedRate: HourlyRate::fromAmount($command->billedRateAmount),
            taskId: $taskId,
        );

        if ($command->comment !== null && $command->comment !== '') {
            $workItem->setNotes($command->comment);
        }

        // Q3.2 role-based managers self-validate
        if ($command->authorIsManager) {
            $workItem->markAsValidated();
        }

        $this->workItemRepository->save($workItem);

        // Dispatch événements Domain (WorkItemRecordedEvent + éventuel
        // WorkItemValidatedEvent si manager) via Symfony Messenger.
        foreach ($workItem->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }

        return $workItem->getId();
    }
}

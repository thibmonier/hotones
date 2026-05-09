<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Repository;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\WorkItem\Entity\WorkItem;
use App\Domain\WorkItem\Exception\WorkItemNotFoundException;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use DateTimeImmutable;

/**
 * EPIC-003 Phase 1 — interface uniquement (impl ACL Phase 2 sprint-021).
 *
 * @see ADR-0008 ACL pattern
 * @see ADR-0013 EPIC-003 scope WorkItem & Profitability
 */
interface WorkItemRepositoryInterface
{
    /**
     * @throws WorkItemNotFoundException
     */
    public function findById(WorkItemId $id): WorkItem;

    public function findByIdOrNull(WorkItemId $id): ?WorkItem;

    /**
     * @return array<WorkItem>
     */
    public function findByProject(ProjectId $projectId): array;

    /**
     * @return array<WorkItem>
     */
    public function findByContributorAndDateRange(
        ContributorId $contributorId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): array;

    /**
     * Sprint-020 ADR-0015 décision Q2 : invariant journalier nécessite charger
     * tous les WorkItems d'un contributeur pour une date donnée afin de calculer
     * `dailyTotal = sum(hours)` avant nouveau record.
     *
     * @return array<WorkItem>
     */
    public function findByContributorAndDate(
        ContributorId $contributorId,
        DateTimeImmutable $date,
    ): array;

    public function save(WorkItem $workItem): void;
}

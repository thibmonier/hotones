<?php

declare(strict_types=1);

namespace App\Domain\EmploymentPeriod\Repository;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\EmploymentPeriod\Snapshot\EmploymentPeriodSnapshot;
use DateTimeImmutable;

/**
 * EPIC-003 Phase 3 (sprint-021 US-100) — interface Domain pour lecture snapshot
 * EmploymentPeriod actif (contributor, date).
 *
 * Implémentation : ACL adapter Infrastructure wrapping legacy flat repo
 * (AT-3.1 ADR-0016 — pattern strangler fig sprints 008-013).
 */
interface EmploymentPeriodRepositoryInterface
{
    /**
     * Trouve la période active pour un contributeur à une date donnée.
     *
     * Active = startDate <= date AND (endDate IS NULL OR endDate >= date).
     *
     * @return EmploymentPeriodSnapshot|null null si aucune période active
     */
    public function findActiveSnapshotForContributor(
        ContributorId $contributorId,
        DateTimeImmutable $date,
    ): ?EmploymentPeriodSnapshot;
}

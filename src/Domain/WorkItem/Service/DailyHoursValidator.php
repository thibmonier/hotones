<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Service;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\EmploymentPeriod\Exception\NoActiveEmploymentPeriodException;
use App\Domain\EmploymentPeriod\Repository\EmploymentPeriodRepositoryInterface;
use App\Domain\WorkItem\Repository\WorkItemRepositoryInterface;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use DateTimeImmutable;

/**
 * Domain Service — calcule dailyMaxHours pour (contributor, date) et détecte
 * dépassement journalier (ADR-0015 invariant journalier + ADR-0016 Q2.4).
 *
 * Pattern strangler fig (AT-3.1 ACL adapter wrapping flat EmploymentPeriod repo).
 *
 * Pas de coupling direct à EmploymentPeriod entity — passe par
 * EmploymentPeriodRepositoryInterface qui retourne EmploymentPeriodSnapshot
 * (Domain DTO).
 */
final readonly class DailyHoursValidator
{
    public function __construct(
        private EmploymentPeriodRepositoryInterface $employmentPeriodRepository,
        private WorkItemRepositoryInterface $workItemRepository,
    ) {
    }

    /**
     * @throws NoActiveEmploymentPeriodException
     */
    public function dailyMaxHours(ContributorId $contributorId, DateTimeImmutable $date): WorkedHours
    {
        $snapshot = $this->employmentPeriodRepository->findActiveSnapshotForContributor($contributorId, $date);

        if ($snapshot === null) {
            throw new NoActiveEmploymentPeriodException($contributorId, $date);
        }

        return $snapshot->dailyMaxHours();
    }

    /**
     * Calcule dailyTotal existant pour (contributor, date) et indique si
     * l'ajout de additionalHours dépasse dailyMaxHours.
     *
     * @throws NoActiveEmploymentPeriodException
     */
    public function isExceeded(
        ContributorId $contributorId,
        DateTimeImmutable $date,
        WorkedHours $additionalHours,
    ): bool {
        $maxHours = $this->dailyMaxHours($contributorId, $date);
        $existingTotal = $this->currentDailyTotal($contributorId, $date);

        $newTotal = $existingTotal + $additionalHours->getValue();

        return $newTotal > $maxHours->getValue();
    }

    /**
     * Retourne dailyTotal existant (somme heures WorkItems déjà saisis pour
     * cette date), 0.0 si aucun WorkItem.
     */
    public function currentDailyTotal(ContributorId $contributorId, DateTimeImmutable $date): float
    {
        $existingItems = $this->workItemRepository->findByContributorAndDate($contributorId, $date);

        $total = 0.0;
        foreach ($existingItems as $workItem) {
            $total += $workItem->getHours()->getValue();
        }

        return $total;
    }
}

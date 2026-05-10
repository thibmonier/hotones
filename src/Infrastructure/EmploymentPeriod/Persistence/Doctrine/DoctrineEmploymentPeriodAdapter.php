<?php

declare(strict_types=1);

namespace App\Infrastructure\EmploymentPeriod\Persistence\Doctrine;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\EmploymentPeriod\Repository\EmploymentPeriodRepositoryInterface;
use App\Domain\EmploymentPeriod\Snapshot\EmploymentPeriodSnapshot;
use App\Domain\EmploymentPeriod\ValueObject\WeeklyHours;
use App\Domain\EmploymentPeriod\ValueObject\WorkTimePercentage;
use App\Entity\EmploymentPeriod as FlatEmploymentPeriod;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * EPIC-003 Phase 3 (sprint-021 US-100) — ACL adapter wrapping legacy flat
 * `App\Entity\EmploymentPeriod` + `App\Repository\EmploymentPeriodRepository`.
 *
 * Pattern strangler fig (AT-3.1 ADR-0016) — pas de migration entity flat,
 * Domain interface emit Snapshot DTO depuis row Doctrine.
 *
 * `ContributorId` legacy uniquement (pattern sprint-020 #207 strangler fig).
 */
final readonly class DoctrineEmploymentPeriodAdapter implements EmploymentPeriodRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findActiveSnapshotForContributor(
        ContributorId $contributorId,
        DateTimeImmutable $date,
    ): ?EmploymentPeriodSnapshot {
        if (!$contributorId->isLegacy()) {
            return null;
        }

        $flat = $this->entityManager->createQueryBuilder()
            ->select('ep')
            ->from(FlatEmploymentPeriod::class, 'ep')
            ->andWhere('ep.contributor = :contributorId')
            ->andWhere('ep.startDate <= :date')
            ->andWhere('ep.endDate IS NULL OR ep.endDate >= :date')
            ->setParameter('contributorId', $contributorId->toLegacyInt())
            ->setParameter('date', $date)
            ->orderBy('ep.startDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$flat instanceof FlatEmploymentPeriod) {
            return null;
        }

        return new EmploymentPeriodSnapshot(
            weeklyHours: WeeklyHours::fromDecimalString($flat->weeklyHours),
            workTimePercentage: WorkTimePercentage::fromDecimalString($flat->workTimePercentage),
        );
    }
}

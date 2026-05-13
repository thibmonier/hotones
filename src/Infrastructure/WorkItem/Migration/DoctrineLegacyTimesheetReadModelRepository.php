<?php

declare(strict_types=1);

namespace App\Infrastructure\WorkItem\Migration;

use App\Domain\WorkItem\Migration\LegacyTimesheetReadModelRepositoryInterface;
use App\Domain\WorkItem\Migration\LegacyTimesheetRecord;
use App\Entity\Timesheet;
use App\Security\CompanyContext;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine adapter for {@see LegacyTimesheetReadModelRepositoryInterface}.
 *
 * Multi-tenant via CompanyContext. Batch iteration via offset pour
 * éviter memory exhaustion sur volumes 2000-10000 timesheets.
 *
 * EPIC-003 Phase 4 sprint-024 US-113 T-113-03.
 */
final readonly class DoctrineLegacyTimesheetReadModelRepository implements LegacyTimesheetReadModelRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyContext $companyContext,
    ) {
    }

    public function countAll(): int
    {
        $company = $this->companyContext->getCurrentCompany();

        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Timesheet::class, 't')
            ->where('t.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findBatch(int $batchSize, int $offset): array
    {
        $company = $this->companyContext->getCurrentCompany();

        $rows = $this->entityManager->createQueryBuilder()
            ->select(
                't.id AS timesheetId',
                'IDENTITY(t.contributor) AS contributorId',
                't.date AS workDate',
                't.hours AS hours',
                't.legacyCostCents AS legacyCostCents',
                't.migratedAt AS migratedAt',
            )
            ->from(Timesheet::class, 't')
            ->where('t.company = :company')
            ->setParameter('company', $company)
            ->orderBy('t.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($batchSize)
            ->getQuery()
            ->getArrayResult();

        $records = [];
        foreach ($rows as $row) {
            $records[] = new LegacyTimesheetRecord(
                timesheetId: (int) $row['timesheetId'],
                contributorId: (int) $row['contributorId'],
                workDate: self::toImmutable($row['workDate']),
                hours: (float) $row['hours'],
                legacyCostCents: $row['legacyCostCents'] !== null ? (int) $row['legacyCostCents'] : null,
                migratedAt: $row['migratedAt'] instanceof DateTimeInterface
                    ? self::toImmutable($row['migratedAt'])
                    : null,
            );
        }

        return $records;
    }

    public function applyMigrationWrites(int $timesheetId, int $costCents, bool $drift, DateTimeImmutable $now): void
    {
        $timesheet = $this->entityManager->find(Timesheet::class, $timesheetId);
        if ($timesheet === null) {
            return;
        }

        // First snapshot : write legacy_cost_cents si null
        if ($timesheet->legacyCostCents === null) {
            $timesheet->legacyCostCents = $costCents;
        }

        $timesheet->legacyCostDrift = $drift;
        $timesheet->migratedAt = $now;

        $this->entityManager->flush();
    }

    private static function toImmutable(DateTimeInterface $date): DateTimeImmutable
    {
        return $date instanceof DateTimeImmutable
            ? $date
            : DateTimeImmutable::createFromInterface($date);
    }
}

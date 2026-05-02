<?php

declare(strict_types=1);

namespace App\Service\Workload;

use App\Repository\StaffingMetricsRepository;
use DateTimeImmutable;

/**
 * Doctrine-backed implementation of WorkloadCalculatorInterface.
 *
 * Reads from `StaffingMetricsRepository` filtered by `yearMonth` +
 * `granularity = monthly`. Returns 0/0 when the contributor has no metrics
 * recorded for the requested month — callers treat that as "no workload",
 * not as an error.
 */
final readonly class DoctrineWorkloadCalculator implements WorkloadCalculatorInterface
{
    public function __construct(
        private StaffingMetricsRepository $staffingMetricsRepository,
    ) {
    }

    public function forContributor(int $contributorId, DateTimeImmutable $month): array
    {
        $yearMonth = $month->format('Y-m');

        $qb = $this->staffingMetricsRepository->createQueryBuilder('sm');
        $qb
            ->leftJoin('sm.dimTime', 'dt')
            ->where('sm.contributor = :contributorId')
            ->andWhere('dt.yearMonth = :yearMonth')
            ->andWhere('sm.granularity = :granularity')
            ->setParameter('contributorId', $contributorId)
            ->setParameter('yearMonth', $yearMonth)
            ->setParameter('granularity', 'monthly')
            ->setMaxResults(1);

        $metrics = $qb->getQuery()->getOneOrNullResult();

        if ($metrics === null) {
            return ['totalDays' => 0.0, 'capacityRate' => 0.0];
        }

        $plannedDays = (float) $metrics->getPlannedDays();
        $availableDays = (float) $metrics->getAvailableDays();
        $capacityRate = $availableDays > 0
            ? ($plannedDays / $availableDays) * 100
            : 0.0;

        return [
            'totalDays' => $plannedDays,
            'capacityRate' => $capacityRate,
        ];
    }
}

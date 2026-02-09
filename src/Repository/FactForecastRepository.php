<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FactForecast;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<FactForecast>
 */
class FactForecastRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, FactForecast::class, $companyContext);
    }

    /**
     * Find latest forecast for a given period and scenario.
     */
    public function findLatestForPeriod(
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        string $scenario,
    ): ?FactForecast {
        return $this
            ->createCompanyQueryBuilder('f')
            ->andWhere('f.periodStart = :start')
            ->andWhere('f.periodEnd = :end')
            ->andWhere('f.scenario = :scenario')
            ->setParameter('start', $periodStart)
            ->setParameter('end', $periodEnd)
            ->setParameter('scenario', $scenario)
            ->orderBy('f.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get forecasts for a date range.
     *
     * @return FactForecast[]
     */
    public function findByDateRange(DateTimeImmutable $start, DateTimeImmutable $end, ?string $scenario = null): array
    {
        $qb = $this
            ->createCompanyQueryBuilder('f')
            ->andWhere('f.periodStart >= :start')
            ->andWhere('f.periodEnd <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('f.periodStart', 'ASC')
            ->addOrderBy('f.scenario', 'ASC');

        if ($scenario !== null) {
            $qb->andWhere('f.scenario = :scenario')->setParameter('scenario', $scenario);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Calculate average accuracy for past forecasts.
     */
    public function calculateAverageAccuracy(string $scenario, int $months = 6): ?float
    {
        $result = $this
            ->createCompanyQueryBuilder('f')
            ->select('AVG(f.accuracy) as avg_accuracy')
            ->andWhere('f.scenario = :scenario')
            ->andWhere('f.accuracy IS NOT NULL')
            ->andWhere('f.createdAt >= :since')
            ->setParameter('scenario', $scenario)
            ->setParameter('since', new DateTimeImmutable("-{$months} months"))
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (float) $result : null;
    }
}

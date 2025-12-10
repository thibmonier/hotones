<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FactForecast;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FactForecast>
 */
class FactForecastRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactForecast::class);
    }

    /**
     * Find latest forecast for a given period and scenario.
     */
    public function findLatestForPeriod(DateTimeImmutable $periodStart, DateTimeImmutable $periodEnd, string $scenario): ?FactForecast
    {
        return $this->createQueryBuilder('f')
            ->where('f.periodStart = :start')
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
        $qb = $this->createQueryBuilder('f')
            ->where('f.periodStart >= :start')
            ->andWhere('f.periodEnd <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('f.periodStart', 'ASC')
            ->addOrderBy('f.scenario', 'ASC');

        if ($scenario !== null) {
            $qb->andWhere('f.scenario = :scenario')
                ->setParameter('scenario', $scenario);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Calculate average accuracy for past forecasts.
     */
    public function calculateAverageAccuracy(string $scenario, int $months = 6): ?float
    {
        $result = $this->createQueryBuilder('f')
            ->select('AVG(f.accuracy) as avg_accuracy')
            ->where('f.scenario = :scenario')
            ->andWhere('f.accuracy IS NOT NULL')
            ->andWhere('f.createdAt >= :since')
            ->setParameter('scenario', $scenario)
            ->setParameter('since', new DateTimeImmutable("-{$months} months"))
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (float) $result : null;
    }
}

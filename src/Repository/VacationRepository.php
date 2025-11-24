<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Vacation;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vacation>
 *
 * @method Vacation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vacation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vacation[]    findAll()
 * @method Vacation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VacationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vacation::class);
    }

    /**
     * Compte le nombre de jours de congés approuvés entre deux dates.
     */
    public function countApprovedDaysBetween(DateTimeInterface $startDate, DateTimeInterface $endDate): float
    {
        $vacations = $this->createQueryBuilder('v')
            ->where('v.status = :approved')
            ->andWhere('v.startDate <= :endDate')
            ->andWhere('v.endDate >= :startDate')
            ->setParameter('approved', 'approved')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        $totalDays = 0.0;
        foreach ($vacations as $vacation) {
            // Calculer le nombre de jours ouvrés entre startDate et endDate
            $start = max($vacation->getStartDate(), $startDate);
            $end   = min($vacation->getEndDate(), $endDate);

            $interval = $start->diff($end);
            $days     = $interval->days + 1; // +1 pour inclure le dernier jour

            $totalDays += $days;
        }

        return $totalDays;
    }
}

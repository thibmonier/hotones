<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Vacation;
use App\Security\CompanyContext;
use DateTimeInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<Vacation>
 *
 * @method Vacation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vacation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vacation[]    findAll()
 * @method Vacation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VacationRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, Vacation::class, $companyContext);
    }

    /**
     * Compte le nombre de jours de congés approuvés entre deux dates.
     */
    public function countApprovedDaysBetween(DateTimeInterface $startDate, DateTimeInterface $endDate): float
    {
        $vacations = $this
            ->createCompanyQueryBuilder('v')
            ->andWhere('v.status = :approved')
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

    /**
     * Récupère les vacations en attente pour une liste de contributeurs.
     * Optimisé pour éviter les N+1 queries.
     *
     * @param array $contributors Array of Contributor entities
     *
     * @return Vacation[]
     */
    public function findPendingForContributors(array $contributors): array
    {
        if (empty($contributors)) {
            return [];
        }

        return $this
            ->createCompanyQueryBuilder('v')
            ->leftJoin('v.contributor', 'c')
            ->addSelect('c')
            ->andWhere('v.contributor IN (:contributors)')
            ->andWhere('v.status = :status')
            ->setParameter('contributors', $contributors)
            ->setParameter('status', 'pending')
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

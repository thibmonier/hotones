<?php

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmploymentPeriod>
 *
 * @method EmploymentPeriod|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmploymentPeriod|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmploymentPeriod[]    findAll()
 * @method EmploymentPeriod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmploymentPeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmploymentPeriod::class);
    }

    /**
     * Trouve les périodes d'emploi avec filtrage optionnel par contributeur.
     */
    public function findWithOptionalContributorFilter(?int $contributorId = null): array
    {
        $queryBuilder = $this->createQueryBuilder('ep')
            ->leftJoin('ep.contributor', 'c')
            ->orderBy('ep.startDate', 'DESC');

        if ($contributorId) {
            $queryBuilder->andWhere('ep.contributor = :contributor')
                ->setParameter('contributor', $contributorId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Vérifie s'il y a des chevauchements de périodes pour un contributeur donné.
     */
    public function hasOverlappingPeriods(EmploymentPeriod $period, ?int $excludeId = null): bool
    {
        if (!$period->getContributor()) {
            return false;
        }

        $queryBuilder = $this->createQueryBuilder('ep')
            ->where('ep.contributor = :contributor')
            ->setParameter('contributor', $period->getContributor());

        if ($excludeId) {
            $queryBuilder->andWhere('ep.id <> :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        // Vérifier les chevauchements
        $endDate = $period->getEndDate();
        if ($endDate) {
            // Période avec date de fin
            $queryBuilder->andWhere(
                '(ep.startDate <= :endDate AND (ep.endDate IS NULL OR ep.endDate >= :startDate))',
            )
            ->setParameter('startDate', $period->getStartDate())
            ->setParameter('endDate', $endDate);
        } else {
            // Période ouverte (sans date de fin)
            $queryBuilder->andWhere(
                '(ep.endDate IS NULL OR ep.endDate >= :startDate)',
            )
            ->setParameter('startDate', $period->getStartDate());
        }

        return $queryBuilder->getQuery()->getOneOrNullResult() !== null;
    }

    /**
     * Récupère les périodes d'emploi actives (sans date de fin ou date de fin dans le futur).
     */
    public function findActivePeriods(): array
    {
        $now = new DateTime();

        return $this->createQueryBuilder('ep')
            ->leftJoin('ep.contributor', 'c')
            ->addSelect('c')
            ->where('ep.endDate IS NULL OR ep.endDate >= :now')
            ->setParameter('now', $now)
            ->orderBy('ep.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les périodes d'emploi pour un contributeur donné.
     */
    public function findByContributor(Contributor $contributor): array
    {
        return $this->createQueryBuilder('ep')
            ->where('ep.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('ep.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère la période d'emploi active actuelle pour un contributeur.
     */
    public function findCurrentPeriodForContributor(Contributor $contributor): ?EmploymentPeriod
    {
        $now = new DateTime();

        return $this->createQueryBuilder('ep')
            ->where('ep.contributor = :contributor')
            ->andWhere('ep.startDate <= :now')
            ->andWhere('ep.endDate IS NULL OR ep.endDate >= :now')
            ->setParameter('contributor', $contributor)
            ->setParameter('now', $now)
            ->orderBy('ep.startDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les périodes d'emploi avec leurs profils associés.
     */
    public function findWithProfiles(): array
    {
        return $this->createQueryBuilder('ep')
            ->leftJoin('ep.contributor', 'c')
            ->leftJoin('ep.profiles', 'p')
            ->addSelect('c', 'p')
            ->orderBy('ep.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le coût total d'une période d'emploi.
     */
    public function calculatePeriodCost(EmploymentPeriod $period): ?float
    {
        if (!$period->getCjm()) {
            return null;
        }

        $endDate             = $period->getEndDate() ?? new DateTime();
        $workingDays         = $this->calculateWorkingDays($period->getStartDate(), $endDate);
        $adjustedWorkingDays = $workingDays * (floatval($period->getWorkTimePercentage()) / 100);

        return $adjustedWorkingDays * floatval($period->getCjm());
    }

    /**
     * Calcule le nombre de jours ouvrés entre deux dates.
     */
    public function calculateWorkingDays(DateTime $startDate, DateTime $endDate): int
    {
        $workingDays = 0;
        $current     = clone $startDate;

        while ($current <= $endDate) {
            // Exclure les weekends (samedi = 6, dimanche = 0)
            $dayOfWeek = $current->format('w');
            if ($dayOfWeek !== '0' && $dayOfWeek !== '6') {
                ++$workingDays;
            }
            $current->modify('+1 day');
        }

        return $workingDays;
    }

    /**
     * Récupère les statistiques des périodes d'emploi.
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('ep');

        // Total des périodes
        $totalPeriods = $qb->select('COUNT(ep.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Périodes actives
        $now           = new DateTime();
        $activePeriods = $this->createQueryBuilder('ep')
            ->select('COUNT(ep.id)')
            ->where('ep.endDate IS NULL OR ep.endDate >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        // Coût moyen des CJM
        $avgCjm = $this->createQueryBuilder('ep')
            ->select('AVG(ep.cjm)')
            ->where('ep.cjm IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_periods'  => $totalPeriods,
            'active_periods' => $activePeriods,
            'average_cjm'    => $avgCjm ? round($avgCjm, 2) : null,
        ];
    }

    /**
     * Compte le nombre de départs (périodes qui se terminent) dans une plage de dates.
     */
    public function countDepartures(DateTimeInterface $startDate, DateTimeInterface $endDate): int
    {
        return (int) $this->createQueryBuilder('ep')
            ->select('COUNT(ep.id)')
            ->where('ep.endDate IS NOT NULL')
            ->andWhere('ep.endDate >= :startDate')
            ->andWhere('ep.endDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre de périodes actives à une date donnée.
     */
    public function countActiveAt(DateTimeInterface $date): int
    {
        return (int) $this->createQueryBuilder('ep')
            ->select('COUNT(ep.id)')
            ->where('ep.startDate <= :date')
            ->andWhere('ep.endDate IS NULL OR ep.endDate >= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve la première période d'emploi d'un contributeur (par ordre chronologique).
     */
    public function findFirstByContributor(Contributor $contributor): ?EmploymentPeriod
    {
        return $this->createQueryBuilder('ep')
            ->where('ep.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('ep.startDate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les périodes d'emploi pour une liste de contributeurs et une plage de dates.
     *
     * @param Contributor[] $contributors
     *
     * @return EmploymentPeriod[]
     */
    public function findByContributorsAndDateRange(array $contributors, DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        if (empty($contributors)) {
            return [];
        }

        $contributorIds = array_map(fn (Contributor $c) => $c->getId(), $contributors);

        return $this->createQueryBuilder('ep')
            ->where('ep.contributor IN (:contributorIds)')
            ->andWhere('ep.startDate <= :endDate')
            ->andWhere('ep.endDate IS NULL OR ep.endDate >= :startDate')
            ->setParameter('contributorIds', $contributorIds)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->leftJoin('ep.contributor', 'c')
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }
}

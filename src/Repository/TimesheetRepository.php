<?php

namespace App\Repository;

use App\Entity\Timesheet;
use App\Entity\Contributor;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Timesheet>
 *
 * @method Timesheet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Timesheet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Timesheet[]    findAll()
 * @method Timesheet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimesheetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Timesheet::class);
    }

    /**
     * Récupère les temps d'un contributeur pour une période donnée
     */
    public function findByContributorAndDateRange(Contributor $contributor, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.contributor = :contributor')
            ->andWhere('t.date BETWEEN :start AND :end')
            ->setParameter('contributor', $contributor)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les temps récents d'un contributeur
     */
    public function findRecentByContributor(Contributor $contributor, int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->where('t.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('t.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les temps pour une période avec filtrage optionnel par projet
     */
    public function findForPeriodWithProject(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?Project $project = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->leftJoin('t.contributor', 'c')
            ->where('t.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('c.name', 'ASC');

        if ($project) {
            $qb->andWhere('p.id = :projectId')
               ->setParameter('projectId', $project->getId());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Calcule le total des heures pour un mois donné
     */
    public function getTotalHoursForMonth(\DateTimeInterface $startDate, \DateTimeInterface $endDate): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.hours)')
            ->where('t.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?: 0;
    }

    /**
     * Récupère les temps avec totaux par projet pour un contributeur
     */
    public function getHoursGroupedByProjectForContributor(Contributor $contributor, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('t')
            ->select('p.name as projectName, SUM(t.hours) as totalHours')
            ->leftJoin('t.project', 'p')
            ->where('t.contributor = :contributor')
            ->andWhere('t.date BETWEEN :start AND :end')
            ->setParameter('contributor', $contributor)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('p.id')
            ->orderBy('totalHours', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un timesheet existant pour éviter les doublons
     */
    public function findExistingTimesheet(Contributor $contributor, Project $project, \DateTimeInterface $date): ?Timesheet
    {
        return $this->findOneBy([
            'contributor' => $contributor,
            'project' => $project,
            'date' => $date
        ]);
    }

    /**
     * Récupère les statistiques de temps par contributeur pour une période
     */
    public function getStatsPerContributor(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('t')
            ->select('c.name as contributorName, SUM(t.hours) as totalHours, COUNT(t.id) as totalEntries')
            ->leftJoin('t.contributor', 'c')
            ->where('t.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('c.id')
            ->orderBy('totalHours', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
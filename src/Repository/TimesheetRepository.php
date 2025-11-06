<?php

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\Project;
use App\Entity\Timesheet;
use DateTimeInterface;
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
     * Récupère les temps d'un contributeur pour une période donnée.
     */
    public function findByContributorAndDateRange(Contributor $contributor, DateTimeInterface $startDate, DateTimeInterface $endDate): array
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
     * Récupère les temps récents d'un contributeur.
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
     * Récupère tous les temps pour une période avec filtrage optionnel par projet.
     */
    public function findForPeriodWithProject(DateTimeInterface $startDate, DateTimeInterface $endDate, ?Project $project = null): array
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
     * Calcule le total des heures pour un mois donné.
     */
    public function getTotalHoursForMonth(DateTimeInterface $startDate, DateTimeInterface $endDate): float
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
     * Récupère les temps avec totaux par projet pour un contributeur.
     */
    public function getHoursGroupedByProjectForContributor(Contributor $contributor, DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        $results = $this->createQueryBuilder('t')
            ->select('p.id AS projectId, p.name AS projectName, pc.name AS projectClient, SUM(t.hours) AS totalHours')
            ->leftJoin('t.project', 'p')
            ->leftJoin('p.client', 'pc')
            ->where('t.contributor = :contributor')
            ->andWhere('t.date BETWEEN :start AND :end')
            ->setParameter('contributor', $contributor)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('p.id, p.name, pc.name')
            ->orderBy('totalHours', 'DESC')
            ->getQuery()
            ->getArrayResult();

        // Reformater les résultats pour exposer un sous-tableau project compatible Twig
        $formatted = [];
        foreach ($results as $r) {
            $formatted[] = [
                'project' => [
                    'id'     => $r['projectId']     ?? null,
                    'name'   => $r['projectName']   ?? null,
                    'client' => $r['projectClient'] ?? null,
                ],
                'totalHours' => (float) ($r['totalHours'] ?? 0),
            ];
        }

        return $formatted;
    }

    /**
     * Trouve un timesheet existant pour éviter les doublons.
     */
    public function findExistingTimesheet(Contributor $contributor, Project $project, DateTimeInterface $date): ?Timesheet
    {
        return $this->findOneBy([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => $date,
        ]);
    }

    /**
     * Trouve un timesheet existant avec tâche spécifique pour éviter les doublons.
     */
    public function findExistingTimesheetWithTask(Contributor $contributor, Project $project, DateTimeInterface $date, ?\App\Entity\ProjectTask $task = null): ?Timesheet
    {
        $criteria = [
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => $date,
        ];

        // Si une tâche est spécifiée, l'ajouter aux critères
        if ($task) {
            $criteria['task'] = $task;
        } else {
            // Si pas de tâche spécifiée, chercher les timesheets sans tâche
            return $this->createQueryBuilder('t')
                ->where('t.contributor = :contributor')
                ->andWhere('t.project = :project')
                ->andWhere('t.date = :date')
                ->andWhere('t.task IS NULL')
                ->setParameter('contributor', $contributor)
                ->setParameter('project', $project)
                ->setParameter('date', $date)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return $this->findOneBy($criteria);
    }

    /**
     * Récupère les statistiques de temps par contributeur pour une période.
     */
    public function getStatsPerContributor(DateTimeInterface $startDate, DateTimeInterface $endDate): array
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

    /**
     * Heures mensuelles (YYYY, MM, totalHours) pour un projet.
     */
    public function getMonthlyHoursForProject(Project $project, ?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('YEAR(t.date) as year, MONTH(t.date) as month, SUM(t.hours) as totalHours')
            ->where('t.project = :project')
            ->setParameter('project', $project)
            ->groupBy('year, month')
            ->orderBy('year', 'ASC')
            ->addOrderBy('month', 'ASC');

        if ($startDate) {
            $qb->andWhere('t.date >= :start')->setParameter('start', $startDate);
        }
        if ($endDate) {
            $qb->andWhere('t.date <= :end')->setParameter('end', $endDate);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Chiffre d'affaires mensuel en régie (TJM contributeur): Σ(hours * (tjm/8)).
     */
    public function getMonthlyRevenueForProjectUsingContributorTjm(Project $project, ?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('YEAR(t.date) as year, MONTH(t.date) as month, SUM(t.hours * (c.tjm/8)) as revenue')
            ->join('t.contributor', 'c')
            ->where('t.project = :project')
            ->setParameter('project', $project)
            ->groupBy('year, month')
            ->orderBy('year', 'ASC')
            ->addOrderBy('month', 'ASC');

        if ($startDate) {
            $qb->andWhere('t.date >= :start')->setParameter('start', $startDate);
        }
        if ($endDate) {
            $qb->andWhere('t.date <= :end')->setParameter('end', $endDate);
        }

        return $qb->getQuery()->getArrayResult();
    }
}

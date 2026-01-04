<?php

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Security\CompanyContext;
use DateTimeInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<Timesheet>
 *
 * @method Timesheet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Timesheet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Timesheet[]    findAll()
 * @method Timesheet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimesheetRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, Timesheet::class, $companyContext);
    }

    /**
     * Récupère les temps d'un contributeur pour une période donnée.
     * Optimisé avec eager loading des relations project et task.
     */
    public function findByContributorAndDateRange(Contributor $contributor, DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        return $this->createCompanyQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->addSelect('p')
            ->leftJoin('t.task', 'ta')
            ->addSelect('ta')
            ->leftJoin('t.subTask', 'st')
            ->addSelect('st')
            ->andWhere('t.contributor = :contributor')
            ->andWhere('t.date BETWEEN :start AND :end')
            ->setParameter('contributor', $contributor)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les temps récents d'un contributeur avec relations préchargées.
     * Optimisé pour éviter les N+1 queries.
     */
    public function findRecentByContributor(Contributor $contributor, int $limit = 5): array
    {
        return $this->createCompanyQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->addSelect('p')
            ->leftJoin('t.task', 'ta')
            ->addSelect('ta')
            ->andWhere('t.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('t.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les temps pour une période avec filtrage optionnel par projet.
     * Optimisé avec eager loading des relations project et contributor.
     */
    public function findForPeriodWithProject(DateTimeInterface $startDate, DateTimeInterface $endDate, ?Project $project = null): array
    {
        $qb = $this->createCompanyQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->addSelect('p')
            ->leftJoin('t.contributor', 'c')
            ->addSelect('c')
            ->leftJoin('t.task', 'ta')
            ->addSelect('ta')
            ->leftJoin('t.subTask', 'st')
            ->addSelect('st')
            ->andWhere('t.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC');

        if ($project) {
            $qb->andWhere('p.id = :projectId')
               ->setParameter('projectId', $project->getId());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère tous les temps pour une période et une liste de projets.
     * Optimisé avec eager loading des relations project et contributor.
     */
    public function findForPeriodWithProjects(DateTimeInterface $startDate, DateTimeInterface $endDate, array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        return $this->createCompanyQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->addSelect('p')
            ->leftJoin('t.contributor', 'c')
            ->addSelect('c')
            ->leftJoin('t.task', 'ta')
            ->addSelect('ta')
            ->leftJoin('t.subTask', 'st')
            ->addSelect('st')
            ->andWhere('t.date BETWEEN :start AND :end')
            ->andWhere('p.id IN (:projectIds)')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('projectIds', $projectIds)
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des heures pour un mois donné.
     */
    public function getTotalHoursForMonth(DateTimeInterface $startDate, DateTimeInterface $endDate): float
    {
        $result = $this->createCompanyQueryBuilder('t')
            ->select('SUM(t.hours)')
            ->andWhere('t.date BETWEEN :start AND :end')
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
        $results = $this->createCompanyQueryBuilder('t')
            ->select('p.id AS projectId, p.name AS projectName, pc.name AS projectClientName, SUM(t.hours) AS totalHours')
            ->leftJoin('t.project', 'p')
            ->leftJoin('p.client', 'pc')
            ->andWhere('t.contributor = :contributor')
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
                    'id'     => $r['projectId']         ?? null,
                    'name'   => $r['projectName']       ?? null,
                    'client' => $r['projectClientName'] ?? null,
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
     * Trouve un timesheet existant pour éviter les doublons (avec tâche et/ou sous-tâche).
     */
    public function findExistingTimesheetWithTaskAndSubTask(
        Contributor $contributor,
        Project $project,
        DateTimeInterface $date,
        ?\App\Entity\ProjectTask $task = null,
        ?\App\Entity\ProjectSubTask $subTask = null
    ): ?Timesheet {
        $qb = $this->createCompanyQueryBuilder('t')
            ->andWhere('t.contributor = :contributor')
            ->andWhere('t.project = :project')
            ->andWhere('t.date = :date')
            ->setParameter('contributor', $contributor)
            ->setParameter('project', $project)
            ->setParameter('date', $date)
            ->setMaxResults(1);

        if ($subTask) {
            $qb->andWhere('t.subTask = :subTask')->setParameter('subTask', $subTask);
        } elseif ($task) {
            $qb->andWhere('t.task = :task')->setParameter('task', $task)
               ->andWhere('t.subTask IS NULL');
        } else {
            $qb->andWhere('t.task IS NULL')->andWhere('t.subTask IS NULL');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * (Déprécié) Trouve un timesheet existant avec tâche spécifique pour éviter les doublons.
     * Conserver pour compat ascendante.
     */
    public function findExistingTimesheetWithTask(Contributor $contributor, Project $project, DateTimeInterface $date, ?\App\Entity\ProjectTask $task = null): ?Timesheet
    {
        return $this->findExistingTimesheetWithTaskAndSubTask($contributor, $project, $date, $task, null);
    }

    /**
     * Récupère les statistiques de temps par contributeur pour une période.
     */
    public function getStatsPerContributor(DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        return $this->createCompanyQueryBuilder('t')
            ->select('CONCAT(c.firstName, \' \', c.lastName) as contributorName, SUM(t.hours) as totalHours, COUNT(t.id) as totalEntries')
            ->leftJoin('t.contributor', 'c')
            ->andWhere('t.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('c.id, c.firstName, c.lastName')
            ->orderBy('totalHours', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques par contributeur restreintes à une liste de projets.
     */
    public function getStatsPerContributorForProjects(DateTimeInterface $startDate, DateTimeInterface $endDate, array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        return $this->createCompanyQueryBuilder('t')
            ->select('CONCAT(c.firstName, \' \', c.lastName) as contributorName, SUM(t.hours) as totalHours, COUNT(t.id) as totalEntries')
            ->leftJoin('t.contributor', 'c')
            ->leftJoin('t.project', 'p')
            ->andWhere('t.date BETWEEN :start AND :end')
            ->andWhere('p.id IN (:projectIds)')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('projectIds', $projectIds)
            ->groupBy('c.id, c.firstName, c.lastName')
            ->orderBy('totalHours', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Total d'heures pour une période restreint à des projets.
     */
    public function getTotalHoursForPeriodAndProjects(DateTimeInterface $startDate, DateTimeInterface $endDate, array $projectIds): float
    {
        if (empty($projectIds)) {
            return 0;
        }

        $result = $this->createCompanyQueryBuilder('t')
            ->select('SUM(t.hours)')
            ->leftJoin('t.project', 'p')
            ->andWhere('t.date BETWEEN :start AND :end')
            ->andWhere('p.id IN (:projectIds)')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('projectIds', $projectIds)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?: 0;
    }

    /**
     * Heures mensuelles (YYYY, MM, totalHours) pour un projet.
     */
    public function getMonthlyHoursForProject(Project $project, ?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): array
    {
        $qb = $this->createCompanyQueryBuilder('t')
            ->select('YEAR(t.date) as year, MONTH(t.date) as month, SUM(t.hours) as totalHours')
            ->andWhere('t.project = :project')
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
     * Le TJM provient de la période d'emploi active à la date du timesheet.
     */
    public function getMonthlyRevenueForProjectUsingContributorTjm(Project $project, ?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): array
    {
        $qb = $this->createCompanyQueryBuilder('t')
            ->select('YEAR(t.date) as year, MONTH(t.date) as month, SUM(t.hours * (COALESCE(ep.tjm, 0)/8)) as revenue')
            ->leftJoin('t.contributor', 'c')
            ->leftJoin('App\\Entity\\EmploymentPeriod', 'ep', 'WITH', 'ep.contributor = c AND ep.startDate <= t.date AND (ep.endDate IS NULL OR ep.endDate >= t.date)')
            ->andWhere('t.project = :project')
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
     * Agrégats période pour un ensemble de projets: totalHours, totalHumanCost, totalRevenue.
     * - Coût humain: Σ(hours × (effectiveCJM)/8), effectiveCJM = ep.cjm × (COALESCE(ep.workTimePercentage, 100)/100)
     * - Revenu: Σ(hours × (effectiveTJM)/8), effectiveTJM = ep.tjm.
     * Les données financières proviennent uniquement de la période d'emploi active à la date du timesheet.
     */
    public function getPeriodAggregatesForProjects(DateTimeInterface $startDate, DateTimeInterface $endDate, array $projectIds): array
    {
        if (empty($projectIds)) {
            return [
                'totalHours'     => 0.0,
                'totalHumanCost' => '0',
                'totalRevenue'   => '0',
            ];
        }

        $qb = $this->createCompanyQueryBuilder('t')
            ->select('COALESCE(SUM(t.hours), 0) AS totalHours')
            ->addSelect('COALESCE(SUM(t.hours * ((COALESCE(ep.cjm, 0) * (COALESCE(ep.workTimePercentage, 100)/100)) / 8)), 0) AS totalHumanCost')
            ->addSelect('COALESCE(SUM(t.hours * (COALESCE(ep.tjm, 0) / 8)), 0) AS totalRevenue')
            ->leftJoin('t.contributor', 'c')
            ->leftJoin('t.project', 'p')
            ->leftJoin('App\\Entity\\EmploymentPeriod', 'ep', 'WITH', 'ep.contributor = c AND ep.startDate <= t.date AND (ep.endDate IS NULL OR ep.endDate >= t.date)')
            ->andWhere('t.date BETWEEN :start AND :end')
            ->andWhere('p.id IN (:projectIds)')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('projectIds', $projectIds);

        $row = $qb->getQuery()->getSingleResult();

        // Retourner sous formes attendues (strings pour montants comme ailleurs)
        return [
            'totalHours'     => (float) ($row['totalHours'] ?? 0),
            'totalHumanCost' => (string) ($row['totalHumanCost'] ?? '0'),
            'totalRevenue'   => (string) ($row['totalRevenue'] ?? '0'),
        ];
    }

    /**
     * Calcule le total des heures saisies par un contributeur pour une date donnée.
     * Utilisé pour valider qu'un contributeur ne dépasse pas 24h/jour.
     *
     * @param Contributor       $contributor Le contributeur
     * @param DateTimeInterface $date        La date
     * @param Timesheet|null    $exclude     Timesheet à exclure du calcul (pour édition)
     *
     * @return float Total des heures
     */
    public function getTotalHoursForContributorAndDate(
        Contributor $contributor,
        DateTimeInterface $date,
        ?Timesheet $exclude = null
    ): float {
        $qb = $this->createCompanyQueryBuilder('t')
            ->select('COALESCE(SUM(t.hours), 0)')
            ->andWhere('t.contributor = :contributor')
            ->andWhere('t.date = :date')
            ->setParameter('contributor', $contributor)
            ->setParameter('date', $date);

        if ($exclude && $exclude->getId()) {
            $qb->andWhere('t.id != :excludeId')
               ->setParameter('excludeId', $exclude->getId());
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }
}

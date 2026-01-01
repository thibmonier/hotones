<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ProjectTask;
use App\Security\CompanyContext;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<ProjectTask>
 */
class ProjectTaskRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, ProjectTask::class, $companyContext);
    }

    /**
     * Trouve toutes les tâches d'un projet triées par position.
     */
    public function findByProjectOrderedByPosition(Project $project): array
    {
        return $this->createCompanyQueryBuilder('t')
            ->andWhere('t.project = :project')
            ->orderBy('t.position', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve la position maximum pour un projet donné.
     */
    public function findMaxPositionForProject(Project $project): int
    {
        return (int) $this->createCompanyQueryBuilder('t')
            ->select('MAX(t.position)')
            ->andWhere('t.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Alias pour findMaxPositionForProject (raccourci).
     */
    public function getMaxPosition(Project $project): int
    {
        return $this->findMaxPositionForProject($project);
    }

    /**
     * Trouve les tâches rentables d'un projet (countsForProfitability = true et type = regular).
     */
    public function findProfitableTasksByProject(Project $project): array
    {
        return $this->createCompanyQueryBuilder('t')
            ->andWhere('t.project = :project')
            ->andWhere('t.countsForProfitability = true')
            ->andWhere('t.type = :type')
            ->orderBy('t.position', 'ASC')
            ->setParameter('project', $project)
            ->setParameter('type', ProjectTask::TYPE_REGULAR)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les tâches par statut pour un projet donné (seulement les tâches rentables).
     */
    public function countProfitableTasksByStatus(Project $project, string $status): int
    {
        return (int) $this->createCompanyQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.project = :project')
            ->andWhere('t.status = :status')
            ->andWhere('t.countsForProfitability = true')
            ->andWhere('t.type = :type')
            ->setParameter('project', $project)
            ->setParameter('status', $status)
            ->setParameter('type', ProjectTask::TYPE_REGULAR)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte toutes les tâches rentables d'un projet.
     */
    public function countProfitableTasks(Project $project): int
    {
        return (int) $this->createCompanyQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.project = :project')
            ->andWhere('t.countsForProfitability = true')
            ->andWhere('t.type = :type')
            ->setParameter('project', $project)
            ->setParameter('type', ProjectTask::TYPE_REGULAR)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les tâches en retard pour un contributeur donné.
     *
     * @return ProjectTask[]
     */
    public function findOverdueTasksByContributor(\App\Entity\Contributor $contributor): array
    {
        return $this->createCompanyQueryBuilder('t')
            ->andWhere('t.assignedContributor = :contributor')
            ->andWhere('t.endDate < :today')
            ->andWhere('t.status != :status_completed')
            ->setParameter('contributor', $contributor)
            ->setParameter('today', new DateTime())
            ->setParameter('status_completed', 'completed')
            ->orderBy('t.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

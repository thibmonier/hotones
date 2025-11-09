<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ProjectTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectTask>
 */
class ProjectTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectTask::class);
    }

    /**
     * Trouve toutes les tâches d'un projet triées par position.
     */
    public function findByProjectOrderedByPosition(Project $project): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.project = :project')
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
        return (int) $this->createQueryBuilder('t')
            ->select('MAX(t.position)')
            ->where('t.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
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
        return $this->createQueryBuilder('t')
            ->where('t.project = :project')
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
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.project = :project')
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
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.project = :project')
            ->andWhere('t.countsForProfitability = true')
            ->andWhere('t.type = :type')
            ->setParameter('project', $project)
            ->setParameter('type', ProjectTask::TYPE_REGULAR)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ProjectSubTask;
use App\Entity\ProjectTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectSubTask>
 */
class ProjectSubTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectSubTask::class);
    }

    /** @return ProjectSubTask[] */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('st')
            ->andWhere('st.project = :project')
            ->setParameter('project', $project)
            ->orderBy('st.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return ProjectSubTask[] */
    public function findByProjectAndStatus(Project $project, string $status): array
    {
        return $this->createQueryBuilder('st')
            ->andWhere('st.project = :project')
            ->andWhere('st.status = :status')
            ->setParameter('project', $project)
            ->setParameter('status', $status)
            ->orderBy('st.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return ProjectSubTask[] */
    public function findByTask(ProjectTask $task): array
    {
        return $this->createQueryBuilder('st')
            ->andWhere('st.task = :task')
            ->setParameter('task', $task)
            ->orderBy('st.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ProjectSubTask;
use App\Entity\ProjectTask;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<ProjectSubTask>
 */
class ProjectSubTaskRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, ProjectSubTask::class, $companyContext);
    }

    /** @return ProjectSubTask[] */
    public function findByProject(Project $project): array
    {
        return $this
            ->createCompanyQueryBuilder('st')
            ->andWhere('st.project = :project')
            ->setParameter('project', $project)
            ->orderBy('st.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return ProjectSubTask[] */
    public function findByProjectAndStatus(Project $project, string $status): array
    {
        return $this
            ->createCompanyQueryBuilder('st')
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
        return $this
            ->createCompanyQueryBuilder('st')
            ->andWhere('st.task = :task')
            ->setParameter('task', $task)
            ->orderBy('st.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ProjectSubTask[]
     */
    public function findByTaskAndAssignee(ProjectTask $task, \App\Entity\Contributor $assignee): array
    {
        return $this
            ->createCompanyQueryBuilder('st')
            ->andWhere('st.task = :task')
            ->andWhere('st.assignee = :assignee')
            ->setParameter('task', $task)
            ->setParameter('assignee', $assignee)
            ->orderBy('st.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

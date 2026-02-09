<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProjectEvent;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<ProjectEvent>
 */
class ProjectEventRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, ProjectEvent::class, $companyContext);
    }

    /**
     * Récupère les événements d'un projet, triés du plus récent au plus ancien.
     *
     * @return ProjectEvent[]
     */
    public function findByProject(int $projectId, int $limit = 50): array
    {
        return $this
            ->createCompanyQueryBuilder('e')
            ->leftJoin('e.actor', 'u')
            ->addSelect('u')
            ->andWhere('e.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

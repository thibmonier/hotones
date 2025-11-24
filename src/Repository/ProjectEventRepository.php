<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProjectEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectEvent>
 */
class ProjectEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectEvent::class);
    }

    /**
     * Récupère les événements d'un projet, triés du plus récent au plus ancien.
     *
     * @return ProjectEvent[]
     */
    public function findByProject(int $projectId, int $limit = 50): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.actor', 'u')
            ->addSelect('u')
            ->where('e.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

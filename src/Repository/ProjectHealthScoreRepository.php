<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ProjectHealthScore;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectHealthScore>
 */
class ProjectHealthScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectHealthScore::class);
    }

    /**
     * Find latest health score for a project.
     */
    public function findLatestForProject(Project $project): ?ProjectHealthScore
    {
        return $this->createQueryBuilder('phs')
            ->where('phs.project = :project')
            ->setParameter('project', $project)
            ->orderBy('phs.calculatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get historical scores for a project.
     *
     * @return ProjectHealthScore[]
     */
    public function findHistoricalScores(Project $project, int $days = 30): array
    {
        $since = new DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('phs')
            ->where('phs.project = :project')
            ->andWhere('phs.calculatedAt >= :since')
            ->setParameter('project', $project)
            ->setParameter('since', $since)
            ->orderBy('phs.calculatedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all projects at risk (warning or critical).
     *
     * @return ProjectHealthScore[]
     */
    public function findProjectsAtRisk(): array
    {
        // Get latest score for each project
        $subQuery = $this->createQueryBuilder('phs2')
            ->select('MAX(phs2.id)')
            ->where('phs2.project = phs.project');

        return $this->createQueryBuilder('phs')
            ->where($this->getEntityManager()->getExpressionBuilder()->in('phs.id', $subQuery->getDQL()))
            ->andWhere('phs.healthLevel IN (:levels)')
            ->setParameter('levels', ['warning', 'critical'])
            ->orderBy('phs.score', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count projects by health level.
     */
    public function countByHealthLevel(): array
    {
        // Get latest score for each project
        $subQuery = $this->createQueryBuilder('phs2')
            ->select('MAX(phs2.id)')
            ->groupBy('phs2.project');

        $results = $this->createQueryBuilder('phs')
            ->select('phs.healthLevel', 'COUNT(phs.id) as count')
            ->where($this->getEntityManager()->getExpressionBuilder()->in('phs.id', $subQuery->getDQL()))
            ->groupBy('phs.healthLevel')
            ->getQuery()
            ->getResult();

        $counts = ['healthy' => 0, 'warning' => 0, 'critical' => 0];
        foreach ($results as $result) {
            $counts[$result['healthLevel']] = (int) $result['count'];
        }

        return $counts;
    }
}

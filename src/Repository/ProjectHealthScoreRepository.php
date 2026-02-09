<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ProjectHealthScore;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<ProjectHealthScore>
 */
class ProjectHealthScoreRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, ProjectHealthScore::class, $companyContext);
    }

    /**
     * Find latest health score for a project.
     */
    public function findLatestForProject(Project $project): ?ProjectHealthScore
    {
        return $this
            ->createCompanyQueryBuilder('phs')
            ->andWhere('phs.project = :project')
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

        return $this
            ->createCompanyQueryBuilder('phs')
            ->andWhere('phs.project = :project')
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
        $subQuery = $this
            ->createCompanyQueryBuilder('phs2')
            ->select('MAX(phs2.id)')
            ->where('phs2.project = phs.project');

        return $this
            ->createCompanyQueryBuilder('phs')
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
        $subQuery = $this->createCompanyQueryBuilder('phs2')->select('MAX(phs2.id)')->groupBy('phs2.project');

        $results = $this
            ->createCompanyQueryBuilder('phs')
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

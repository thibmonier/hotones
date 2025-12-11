<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\OnboardingTask;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OnboardingTask>
 */
class OnboardingTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OnboardingTask::class);
    }

    /**
     * Find tasks for a contributor.
     *
     * @return OnboardingTask[]
     */
    public function findByContributor(Contributor $contributor): array
    {
        return $this->createQueryBuilder('ot')
            ->where('ot.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('ot.orderNum', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending tasks for a contributor.
     *
     * @return OnboardingTask[]
     */
    public function findPendingForContributor(Contributor $contributor): array
    {
        return $this->createQueryBuilder('ot')
            ->where('ot.contributor = :contributor')
            ->andWhere('ot.status != :completed')
            ->setParameter('contributor', $contributor)
            ->setParameter('completed', 'termine')
            ->orderBy('ot.dueDate', 'ASC')
            ->addOrderBy('ot.orderNum', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find overdue tasks for a contributor.
     *
     * @return OnboardingTask[]
     */
    public function findOverdueForContributor(Contributor $contributor): array
    {
        return $this->createQueryBuilder('ot')
            ->where('ot.contributor = :contributor')
            ->andWhere('ot.status != :completed')
            ->andWhere('ot.dueDate < :now')
            ->setParameter('contributor', $contributor)
            ->setParameter('completed', 'termine')
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('ot.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate completion percentage for a contributor.
     */
    public function calculateProgress(Contributor $contributor): int
    {
        $qb = $this->createQueryBuilder('ot')
            ->select('COUNT(ot.id)')
            ->where('ot.contributor = :contributor')
            ->setParameter('contributor', $contributor);

        $total = (int) $qb->getQuery()->getSingleScalarResult();

        if (0 === $total) {
            return 100; // No tasks = 100% complete
        }

        $completed = (int) $qb
            ->andWhere('ot.status = :completed')
            ->setParameter('completed', 'termine')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) round(($completed / $total) * 100);
    }

    /**
     * Get onboarding statistics for team.
     *
     * @return array{contributor_id: int, contributor_name: string, total: int, completed: int, progress: int, overdue: int}[]
     */
    public function getTeamStatistics(array $contributorIds = []): array
    {
        $qb = $this->createQueryBuilder('ot')
            ->leftJoin('ot.contributor', 'c')
            ->select('IDENTITY(ot.contributor) as contributor_id')
            ->addSelect('CONCAT(c.firstName, \' \', c.lastName) as contributor_name')
            ->addSelect('COUNT(ot.id) as total')
            ->addSelect('SUM(CASE WHEN ot.status = :completed THEN 1 ELSE 0 END) as completed')
            ->addSelect('SUM(CASE WHEN ot.status != :completed AND ot.dueDate < :now THEN 1 ELSE 0 END) as overdue')
            ->setParameter('completed', 'termine')
            ->setParameter('now', new DateTimeImmutable())
            ->groupBy('ot.contributor, c.firstName, c.lastName');

        if (!empty($contributorIds)) {
            $qb->andWhere('ot.contributor IN (:contributorIds)')
                ->setParameter('contributorIds', $contributorIds);
        }

        $results = $qb->getQuery()->getResult();

        // Calculate progress percentage and ensure proper types
        $typedResults = [];
        foreach ($results as $result) {
            $total     = (int) $result['total'];
            $completed = (int) $result['completed'];
            $overdue   = (int) $result['overdue'];

            $typedResults[] = [
                'contributor_id'   => (int) $result['contributor_id'],
                'contributor_name' => (string) $result['contributor_name'],
                'total'            => $total,
                'completed'        => $completed,
                'overdue'          => $overdue,
                'progress'         => $total > 0 ? (int) round(($completed / $total) * 100) : 100,
            ];
        }

        return $typedResults;
    }
}

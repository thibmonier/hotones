<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\PerformanceReview;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PerformanceReview>
 */
class PerformanceReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerformanceReview::class);
    }

    /**
     * Find reviews by year.
     *
     * @return PerformanceReview[]
     */
    public function findByYear(int $year): array
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.year = :year')
            ->setParameter('year', $year)
            ->orderBy('pr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reviews for a specific contributor.
     *
     * @return PerformanceReview[]
     */
    public function findByContributor(Contributor $contributor): array
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('pr.year', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reviews managed by a specific user.
     *
     * @return PerformanceReview[]
     */
    public function findByManager(User $manager): array
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.manager = :manager')
            ->setParameter('manager', $manager)
            ->orderBy('pr.year', 'DESC')
            ->addOrderBy('pr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reviews by status.
     *
     * @return PerformanceReview[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.status = :status')
            ->setParameter('status', $status)
            ->orderBy('pr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending reviews for a contributor.
     *
     * @return PerformanceReview[]
     */
    public function findPendingForContributor(Contributor $contributor): array
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.contributor = :contributor')
            ->andWhere('pr.status != :validated')
            ->setParameter('contributor', $contributor)
            ->setParameter('validated', 'validee')
            ->orderBy('pr.year', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if review exists for contributor and year.
     */
    public function existsForContributorAndYear(Contributor $contributor, int $year): bool
    {
        $count = $this->createQueryBuilder('pr')
            ->select('COUNT(pr.id)')
            ->where('pr.contributor = :contributor')
            ->andWhere('pr.year = :year')
            ->setParameter('contributor', $contributor)
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Get statistics for a year.
     *
     * @return array{total: int, en_attente: int, auto_eval_faite: int, eval_manager_faite: int, validee: int}
     */
    public function getStatsByYear(int $year): array
    {
        $results = $this->createQueryBuilder('pr')
            ->select('pr.status, COUNT(pr.id) as count')
            ->where('pr.year = :year')
            ->setParameter('year', $year)
            ->groupBy('pr.status')
            ->getQuery()
            ->getResult();

        $stats = [
            'total'              => 0,
            'en_attente'         => 0,
            'auto_eval_faite'    => 0,
            'eval_manager_faite' => 0,
            'validee'            => 0,
        ];

        foreach ($results as $result) {
            $status         = $result['status'];
            $count          = (int) $result['count'];
            $stats[$status] = $count;
            $stats['total'] += $count;
        }

        return $stats;
    }
}

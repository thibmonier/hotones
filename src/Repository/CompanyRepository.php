<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Company;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CompanyRepository - Repository for Company entities.
 *
 * Note: This repository does NOT extend CompanyAwareRepository because
 * Company is the root tenant entity itself.
 *
 * @extends ServiceEntityRepository<Company>
 *
 * @method Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Company[]    findAll()
 * @method Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * Find company by slug.
     *
     * @param string $slug Company slug
     *
     * @return Company|null Company or null if not found
     */
    public function findOneBySlug(string $slug): ?Company
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Find all active companies.
     *
     * @return array<int, Company> Array of active companies
     */
    public function findActiveCompanies(): array
    {
        return $this
            ->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', Company::STATUS_ACTIVE)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find companies by subscription tier.
     *
     * @param string $tier Subscription tier (starter/professional/enterprise)
     *
     * @return array<int, Company> Array of companies
     */
    public function findBySubscriptionTier(string $tier): array
    {
        return $this
            ->createQueryBuilder('c')
            ->where('c.subscriptionTier = :tier')
            ->setParameter('tier', $tier)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find companies with expiring trials (next N days).
     *
     * @param int $days Number of days to look ahead
     *
     * @return array<int, Company> Array of companies with expiring trials
     */
    public function findExpiringTrials(int $days = 7): array
    {
        $now    = new DateTimeImmutable();
        $future = $now->modify("+{$days} days");

        return $this
            ->createQueryBuilder('c')
            ->where('c.status = :status')
            ->andWhere('c.trialEndsAt BETWEEN :now AND :future')
            ->setParameter('status', Company::STATUS_TRIAL)
            ->setParameter('now', $now)
            ->setParameter('future', $future)
            ->orderBy('c.trialEndsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count companies by status.
     *
     * @return array<string, int> Status counts
     */
    public function countByStatus(): array
    {
        $results = $this
            ->createQueryBuilder('c')
            ->select('c.status, COUNT(c.id) as count')
            ->groupBy('c.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['status']] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * Search companies by name or slug.
     *
     * @param string $query Search query
     * @param int    $limit Maximum results
     *
     * @return array<int, Company> Array of matching companies
     */
    public function search(string $query, int $limit = 20): array
    {
        return $this
            ->createQueryBuilder('c')
            ->where('c.name LIKE :query')
            ->orWhere('c.slug LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults($limit)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if slug is available.
     *
     * @param string   $slug      Slug to check
     * @param int|null $excludeId Company ID to exclude (for updates)
     *
     * @return bool True if available, false if taken
     */
    public function isSlugAvailable(string $slug, ?int $excludeId = null): bool
    {
        $qb = $this
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId) {
            $qb->andWhere('c.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        $count = $qb->getQuery()->getSingleScalarResult();

        return $count === 0;
    }
}

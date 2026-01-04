<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BusinessUnit;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BusinessUnitRepository - Repository for BusinessUnit entities.
 *
 * @extends CompanyAwareRepository<BusinessUnit>
 *
 * @method BusinessUnit|null find($id, $lockMode = null, $lockVersion = null)
 * @method BusinessUnit|null findOneBy(array $criteria, array $orderBy = null)
 * @method BusinessUnit[]    findAll()
 * @method BusinessUnit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BusinessUnitRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, BusinessUnit::class, $companyContext);
    }

    /**
     * Find all active business units for current company.
     *
     * @return array<int, BusinessUnit> Array of active business units
     */
    public function findActiveBusinessUnits(): array
    {
        return $this->createCompanyQueryBuilder('bu')
            ->andWhere('bu.active = :active')
            ->setParameter('active', true)
            ->orderBy('bu.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find root business units (no parent) for current company.
     *
     * @return array<int, BusinessUnit> Array of root business units
     */
    public function findRootBusinessUnits(): array
    {
        return $this->createCompanyQueryBuilder('bu')
            ->andWhere('bu.parent IS NULL')
            ->andWhere('bu.active = :active')
            ->setParameter('active', true)
            ->orderBy('bu.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find children of a business unit.
     *
     * @param BusinessUnit $parent     Parent business unit
     * @param bool         $activeOnly Include only active BUs
     *
     * @return array<int, BusinessUnit> Array of child business units
     */
    public function findChildren(BusinessUnit $parent, bool $activeOnly = true): array
    {
        $qb = $this->createCompanyQueryBuilder('bu')
            ->andWhere('bu.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('bu.name', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('bu.active = :active')
                ->setParameter('active', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get hierarchical tree of business units for current company.
     *
     * Returns array of root BUs with their descendants loaded recursively.
     *
     * @return array<int, BusinessUnit> Hierarchical tree of business units
     */
    public function findHierarchicalTree(): array
    {
        // Get all BUs for current company
        $allBUs = $this->createCompanyQueryBuilder('bu')
            ->andWhere('bu.active = :active')
            ->setParameter('active', true)
            ->orderBy('bu.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Build tree structure
        $tree    = [];
        $indexed = [];

        // Index by ID
        foreach ($allBUs as $bu) {
            $indexed[$bu->getId()] = $bu;
        }

        // Build hierarchy
        foreach ($allBUs as $bu) {
            if ($bu->getParent() === null) {
                // Root BU
                $tree[] = $bu;
            }
        }

        return $tree;
    }

    /**
     * Find business units by manager.
     *
     * @param int $managerId User ID of manager
     *
     * @return array<int, BusinessUnit> Array of business units
     */
    public function findByManager(int $managerId): array
    {
        return $this->createCompanyQueryBuilder('bu')
            ->join('bu.manager', 'm')
            ->andWhere('m.id = :managerId')
            ->setParameter('managerId', $managerId)
            ->orderBy('bu.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search business units by name for current company.
     *
     * @param string $query Search query
     * @param int    $limit Maximum results
     *
     * @return array<int, BusinessUnit> Array of matching business units
     */
    public function search(string $query, int $limit = 20): array
    {
        return $this->createCompanyQueryBuilder('bu')
            ->andWhere('bu.name LIKE :query OR bu.description LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->andWhere('bu.active = :active')
            ->setParameter('active', true)
            ->setMaxResults($limit)
            ->orderBy('bu.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count business units by depth level for current company.
     *
     * @return array<int, int> Depth level counts
     */
    public function countByDepth(): array
    {
        $allBUs = $this->findAllForCurrentCompany();
        $counts = [];

        foreach ($allBUs as $bu) {
            $depth          = $bu->getDepth();
            $counts[$depth] = ($counts[$depth] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }
}

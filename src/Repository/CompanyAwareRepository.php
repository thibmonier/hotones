<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Interface\CompanyOwnedInterface;
use App\Security\CompanyContext;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CompanyAwareRepository - Base repository class for tenant-scoped entities.
 *
 * All repositories for entities implementing CompanyOwnedInterface should
 * extend this class to benefit from automatic company scoping.
 *
 * Key Features:
 * - createCompanyQueryBuilder() automatically adds WHERE company = :company
 * - Standard methods for common operations (findAll, findById, count)
 * - Prevents accidental cross-tenant queries
 * - Explicit company scoping (no "magic" filters)
 *
 * Usage Example:
 * ```php
 * class ProjectRepository extends CompanyAwareRepository
 * {
 *     public function __construct(
 *         ManagerRegistry $registry,
 *         CompanyContext $companyContext
 *     ) {
 *         parent::__construct($registry, Project::class, $companyContext);
 *     }
 *
 *     public function findActiveProjects(): array
 *     {
 *         return $this->createCompanyQueryBuilder('p')
 *             ->andWhere('p.status = :status')
 *             ->setParameter('status', 'active')
 *             ->getQuery()
 *             ->getResult();
 *     }
 * }
 * ```
 *
 * @template T of CompanyOwnedInterface
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class CompanyAwareRepository extends ServiceEntityRepository
{
    protected CompanyContext $companyContext;

    /**
     * @param ManagerRegistry $registry       Doctrine registry
     * @param class-string<T> $entityClass    Entity class name
     * @param CompanyContext  $companyContext Company context service
     */
    public function __construct(
        ManagerRegistry $registry,
        string $entityClass,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, $entityClass);
        $this->companyContext = $companyContext;
    }

    /**
     * Create QueryBuilder with automatic company scoping.
     *
     * This adds WHERE alias.company = :company to the query automatically,
     * ensuring all queries are scoped to the current company.
     *
     * @param string      $alias   Entity alias (e.g., 'p' for Project)
     * @param string|null $indexBy Optional index field
     *
     * @return QueryBuilder Query builder with company filter applied
     */
    protected function createCompanyQueryBuilder(string $alias, ?string $indexBy = null): QueryBuilder
    {
        $company = $this->companyContext->getCurrentCompany();

        return $this->createQueryBuilder($alias, $indexBy)
            ->andWhere("{$alias}.company = :company")
            ->setParameter('company', $company);
    }

    /**
     * Create QueryBuilder for specific company (SUPERADMIN use only).
     *
     * This allows SUPERADMIN to query entities from any company.
     * Regular users should use createCompanyQueryBuilder() instead.
     *
     * @param Company     $company Company to query
     * @param string      $alias   Entity alias
     * @param string|null $indexBy Optional index field
     *
     * @return QueryBuilder Query builder scoped to specified company
     */
    protected function createQueryBuilderForCompany(
        Company $company,
        string $alias,
        ?string $indexBy = null
    ): QueryBuilder {
        return $this->createQueryBuilder($alias, $indexBy)
            ->andWhere("{$alias}.company = :company")
            ->setParameter('company', $company);
    }

    /**
     * Find all entities for current company.
     *
     * @param array<string, string> $orderBy Order by fields (e.g., ['name' => 'ASC'])
     *
     * @return array<int, T> Array of entities
     */
    public function findAllForCurrentCompany(array $orderBy = []): array
    {
        $qb = $this->createCompanyQueryBuilder('e');

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("e.{$field}", $direction);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find one entity by ID with company isolation.
     *
     * This method ensures the entity belongs to the current company.
     * Returns null if entity doesn't exist or belongs to another company.
     *
     * @param int $id Entity ID
     *
     * @return T|null Entity or null if not found/not accessible
     */
    public function findOneByIdForCompany(int $id): ?object
    {
        return $this->createCompanyQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find entities by criteria for current company.
     *
     * @param array<string, mixed>       $criteria Search criteria
     * @param array<string, string>|null $orderBy  Order by fields
     * @param int|null                   $limit    Maximum results
     * @param int|null                   $offset   Result offset
     *
     * @return array<int, T> Array of entities
     */
    public function findByForCurrentCompany(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $qb = $this->createCompanyQueryBuilder('e');

        foreach ($criteria as $field => $value) {
            if ($value === null) {
                $qb->andWhere("e.{$field} IS NULL");
            } else {
                $qb->andWhere("e.{$field} = :{$field}")
                    ->setParameter($field, $value);
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy("e.{$field}", $direction);
            }
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count entities for current company.
     *
     * @param array<string, mixed> $criteria Optional search criteria
     *
     * @return int Number of entities
     */
    public function countForCurrentCompany(array $criteria = []): int
    {
        $qb = $this->createCompanyQueryBuilder('e')
            ->select('COUNT(e.id)');

        foreach ($criteria as $field => $value) {
            if ($value === null) {
                $qb->andWhere("e.{$field} IS NULL");
            } else {
                $qb->andWhere("e.{$field} = :{$field}")
                    ->setParameter($field, $value);
            }
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Delete entity by ID for current company.
     *
     * This method ensures the entity belongs to the current company before deletion.
     *
     * @param int $id Entity ID
     *
     * @return bool True if deleted, false if not found/not accessible
     */
    public function deleteByIdForCompany(int $id): bool
    {
        $entity = $this->findOneByIdForCompany($id);

        if (!$entity) {
            return false;
        }

        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Check if entity exists for current company.
     *
     * @param int $id Entity ID
     *
     * @return bool True if exists and belongs to current company
     */
    public function existsForCompany(int $id): bool
    {
        $count = $this->createCompanyQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}

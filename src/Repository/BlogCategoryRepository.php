<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BlogCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BlogCategoryRepository - Repository for global blog categories.
 *
 * Categories are NOT multi-tenant (shared across all companies).
 *
 * @extends ServiceEntityRepository<BlogCategory>
 */
class BlogCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogCategory::class);
    }

    /**
     * Find all active categories ordered by name.
     *
     * @return array<BlogCategory>
     */
    public function findActive(): array
    {
        return $this
            ->createQueryBuilder('c')
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find category by slug.
     */
    public function findBySlug(string $slug): ?BlogCategory
    {
        return $this
            ->createQueryBuilder('c')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all categories with post count.
     *
     * @return array<BlogCategory>
     */
    public function findWithPostCount(): array
    {
        return $this
            ->createQueryBuilder('c')
            ->leftJoin('c.posts', 'p')
            ->addSelect('p')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active categories with at least one published post.
     *
     * @return array<BlogCategory>
     */
    public function findActiveWithPublishedPosts(): array
    {
        return $this
            ->createQueryBuilder('c')
            ->leftJoin('c.posts', 'p')
            ->andWhere('c.active = :active')
            ->andWhere('p.status = :status')
            ->setParameter('active', true)
            ->setParameter('status', 'published')
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

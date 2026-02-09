<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BlogTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BlogTagRepository - Repository for global blog tags.
 *
 * Tags are NOT multi-tenant (shared across all companies).
 *
 * @extends ServiceEntityRepository<BlogTag>
 */
class BlogTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogTag::class);
    }

    /**
     * Find tag by slug.
     */
    public function findBySlug(string $slug): ?BlogTag
    {
        return $this
            ->createQueryBuilder('t')
            ->andWhere('t.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all tags ordered by name.
     *
     * @return array<BlogTag>
     */
    public function findAllOrdered(): array
    {
        return $this
            ->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find most popular tags (with most published posts).
     *
     * @return array<BlogTag>
     */
    public function findPopular(int $limit = 10): array
    {
        return $this
            ->createQueryBuilder('t')
            ->leftJoin('t.posts', 'p')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'published')
            ->groupBy('t.id')
            ->orderBy('COUNT(p.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find or create a tag by name.
     *
     * Useful for auto-creating tags from form input.
     */
    public function findOrCreate(string $name): BlogTag
    {
        $tag = $this->findOneBy(['name' => $name]);

        if (!$tag) {
            $tag = new BlogTag();
            $tag->setName($name);
            $this->getEntityManager()->persist($tag);
            $this->getEntityManager()->flush();
        }

        return $tag;
    }

    /**
     * Find tags with at least one published post.
     *
     * @return array<BlogTag>
     */
    public function findWithPublishedPosts(): array
    {
        return $this
            ->createQueryBuilder('t')
            ->leftJoin('t.posts', 'p')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'published')
            ->groupBy('t.id')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

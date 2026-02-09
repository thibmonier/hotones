<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BlogCategory;
use App\Entity\BlogPost;
use App\Entity\BlogTag;
use App\Entity\Company;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * BlogPostRepository - Multi-tenant repository for blog posts.
 *
 * Extends CompanyAwareRepository to automatically filter by current company.
 *
 * @extends CompanyAwareRepository<BlogPost>
 */
class BlogPostRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, BlogPost::class, $companyContext);
    }

    /**
     * Find published posts for current company with pagination.
     *
     * @return array<BlogPost>
     */
    public function findPublishedForCompany(?int $limit = null, ?int $offset = null): array
    {
        $qb = $this
            ->createCompanyQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('p.publishedAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count published posts for current company.
     */
    public function countPublishedForCompany(): int
    {
        return (int) $this
            ->createCompanyQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find posts by status for current company.
     *
     * @param array<string, string> $orderBy
     *
     * @return array<BlogPost>
     */
    public function findByStatusForCompany(string $status, array $orderBy = ['publishedAt' => 'DESC']): array
    {
        $qb = $this->createCompanyQueryBuilder('p')->andWhere('p.status = :status')->setParameter('status', $status);

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("p.{$field}", $direction);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find published posts by category for current company.
     *
     * @return array<BlogPost>
     */
    public function findByCategoryForCompany(?BlogCategory $category): array
    {
        $qb = $this
            ->createCompanyQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('p.publishedAt', 'DESC');

        if ($category !== null) {
            $qb->andWhere('p.category = :category')->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find published posts by tag for current company.
     *
     * @return array<BlogPost>
     */
    public function findByTagForCompany(BlogTag $tag): array
    {
        return $this
            ->createCompanyQueryBuilder('p')
            ->leftJoin('p.tags', 't')
            ->andWhere('t = :tag')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('tag', $tag)
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent published posts (for sidebar).
     *
     * @return array<BlogPost>
     */
    public function findRecentPublished(int $limit = 5): array
    {
        return $this
            ->createCompanyQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search published posts by query string (title, content, excerpt).
     *
     * @return array<BlogPost>
     */
    public function searchPublishedForCompany(string $query): array
    {
        return $this
            ->createCompanyQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->andWhere('(p.title LIKE :query OR p.content LIKE :query OR p.excerpt LIKE :query)')
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find one published post by slug for current company.
     */
    public function findPublishedBySlug(string $slug): ?BlogPost
    {
        return $this
            ->createCompanyQueryBuilder('p')
            ->leftJoin('p.author', 'a')
            ->addSelect('a')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.tags', 't')
            ->addSelect('t')
            ->andWhere('p.slug = :slug')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('slug', $slug)
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find related posts (same category, excluding current post).
     *
     * @return array<BlogPost>
     */
    public function findRelatedPosts(BlogPost $post, int $limit = 3): array
    {
        $qb = $this
            ->createCompanyQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->andWhere('p.id != :currentId')
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('currentId', $post->getId())
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit);

        if ($post->getCategory() !== null) {
            $qb->andWhere('p.category = :category')->setParameter('category', $post->getCategory());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find one by slug for admin (any status).
     */
    public function findOneBySlugForAdmin(string $slug): ?BlogPost
    {
        return $this
            ->createCompanyQueryBuilder('p')
            ->leftJoin('p.author', 'a')
            ->addSelect('a')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.tags', 't')
            ->addSelect('t')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find published posts (public access, works without authentication).
     *
     * If company is provided, filters by that company. Otherwise, returns posts
     * from the first active company (for public pages without authentication).
     *
     * @return array<BlogPost>
     */
    public function findPublishedPublic(?int $limit = null, ?int $offset = null): array
    {
        $company = $this->getCompanyForPublicAccess();

        if ($company === null) {
            return [];
        }

        $qb = $this
            ->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('company', $company)
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('p.publishedAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count published posts (public access).
     */
    public function countPublishedPublic(): int
    {
        $company = $this->getCompanyForPublicAccess();

        if ($company === null) {
            return 0;
        }

        return (int) $this
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.company = :company')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('company', $company)
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find published post by slug (public access).
     */
    public function findPublishedBySlugPublic(string $slug): ?BlogPost
    {
        $company = $this->getCompanyForPublicAccess();

        if ($company === null) {
            return null;
        }

        return $this
            ->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')
            ->addSelect('a')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->leftJoin('p.tags', 't')
            ->addSelect('t')
            ->andWhere('p.company = :company')
            ->andWhere('p.slug = :slug')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('company', $company)
            ->setParameter('slug', $slug)
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find published posts by category (public access).
     *
     * @return array<BlogPost>
     */
    public function findByCategoryPublic(?BlogCategory $category): array
    {
        $company = $this->getCompanyForPublicAccess();

        if ($company === null) {
            return [];
        }

        $qb = $this
            ->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('company', $company)
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('p.publishedAt', 'DESC');

        if ($category !== null) {
            $qb->andWhere('p.category = :category')->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find published posts by tag (public access).
     *
     * @return array<BlogPost>
     */
    public function findByTagPublic(BlogTag $tag): array
    {
        $company = $this->getCompanyForPublicAccess();

        if ($company === null) {
            return [];
        }

        return $this
            ->createQueryBuilder('p')
            ->leftJoin('p.tags', 't')
            ->andWhere('p.company = :company')
            ->andWhere('t = :tag')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('company', $company)
            ->setParameter('tag', $tag)
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent published posts (public access, for sidebar).
     *
     * @return array<BlogPost>
     */
    public function findRecentPublishedPublic(int $limit = 5): array
    {
        $company = $this->getCompanyForPublicAccess();

        if ($company === null) {
            return [];
        }

        return $this
            ->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('company', $company)
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find related posts (public access).
     *
     * @return array<BlogPost>
     */
    public function findRelatedPostsPublic(BlogPost $post, int $limit = 3): array
    {
        $company = $this->getCompanyForPublicAccess();

        if ($company === null) {
            return [];
        }

        $qb = $this
            ->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->andWhere('p.status = :status')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->andWhere('p.id != :currentId')
            ->setParameter('company', $company)
            ->setParameter('status', BlogPost::STATUS_PUBLISHED)
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('currentId', $post->getId())
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit);

        if ($post->getCategory() !== null) {
            $qb->andWhere('p.category = :category')->setParameter('category', $post->getCategory());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get company for public access (without authentication).
     *
     * If user is authenticated, uses their company context.
     * Otherwise, returns the first active company for public blog pages.
     */
    private function getCompanyForPublicAccess(): ?Company
    {
        try {
            return $this->companyContext->getCurrentCompany();
        } catch (Exception) {
            // User not authenticated - get first active company for public pages
            return $this
                ->getEntityManager()
                ->getRepository(Company::class)
                ->findOneBy(['status' => Company::STATUS_ACTIVE], ['id' => 'ASC']);
        }
    }
}

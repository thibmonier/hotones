<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Badge;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<Badge>
 */
class BadgeRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, Badge::class, $companyContext);
    }

    /**
     * Récupère tous les badges actifs.
     *
     * @return Badge[]
     */
    public function findAllActive(): array
    {
        return $this
            ->createCompanyQueryBuilder('b')
            ->andWhere('b.active = :active')
            ->setParameter('active', true)
            ->orderBy('b.category', 'ASC')
            ->addOrderBy('b.xpReward', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les badges par catégorie.
     *
     * @return Badge[]
     */
    public function findByCategory(string $category): array
    {
        return $this
            ->createCompanyQueryBuilder('b')
            ->andWhere('b.category = :category')
            ->andWhere('b.active = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('b.xpReward', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les statistiques des badges.
     */
    public function getBadgeStats(): array
    {
        return [
            'total'       => $this->countForCurrentCompany(['active' => true]),
            'by_category' => $this->countByCategory(),
        ];
    }

    private function countByCategory(): array
    {
        $results = $this
            ->createCompanyQueryBuilder('b')
            ->select('b.category, COUNT(b.id) as count')
            ->andWhere('b.active = :active')
            ->setParameter('active', true)
            ->groupBy('b.category')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['category']] = (int) $result['count'];
        }

        return $stats;
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Badge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Badge>
 */
class BadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Badge::class);
    }

    /**
     * Récupère tous les badges actifs.
     *
     * @return Badge[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.active = :active')
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
        return $this->createQueryBuilder('b')
            ->where('b.category = :category')
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
            'total'       => $this->count(['active' => true]),
            'by_category' => $this->countByCategory(),
        ];
    }

    private function countByCategory(): array
    {
        $results = $this->createQueryBuilder('b')
            ->select('b.category, COUNT(b.id) as count')
            ->where('b.active = :active')
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

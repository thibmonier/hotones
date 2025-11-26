<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Achievement;
use App\Entity\Badge;
use App\Entity\Contributor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Achievement>
 */
class AchievementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Achievement::class);
    }

    /**
     * Récupère les achievements d'un contributeur.
     *
     * @return Achievement[]
     */
    public function findByContributor(Contributor $contributor): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.badge', 'b')
            ->where('a.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('a.unlockedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un contributeur a déjà unlocked un badge.
     */
    public function hasAchievement(Contributor $contributor, Badge $badge): bool
    {
        return $this->count([
            'contributor' => $contributor,
            'badge'       => $badge,
        ]) > 0;
    }

    /**
     * Récupère les derniers achievements débloqués.
     *
     * @return Achievement[]
     */
    public function findRecentAchievements(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.contributor', 'c')
            ->leftJoin('a.badge', 'b')
            ->orderBy('a.unlockedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les achievements non notifiés.
     *
     * @return Achievement[]
     */
    public function findUnnotified(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.notified = :notified')
            ->setParameter('notified', false)
            ->orderBy('a.unlockedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'achievements par contributeur.
     */
    public function countByContributor(Contributor $contributor): int
    {
        return $this->count(['contributor' => $contributor]);
    }
}

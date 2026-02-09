<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Achievement;
use App\Entity\Badge;
use App\Entity\Contributor;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<Achievement>
 */
class AchievementRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, Achievement::class, $companyContext);
    }

    /**
     * Récupère les achievements d'un contributeur.
     *
     * @return Achievement[]
     */
    public function findByContributor(Contributor $contributor): array
    {
        return $this
            ->createCompanyQueryBuilder('a')
            ->leftJoin('a.badge', 'b')
            ->andWhere('a.contributor = :contributor')
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
        return $this->countForCurrentCompany([
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
        return $this
            ->createCompanyQueryBuilder('a')
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
        return $this
            ->createCompanyQueryBuilder('a')
            ->andWhere('a.notified = :notified')
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
        return $this->countForCurrentCompany(['contributor' => $contributor]);
    }
}

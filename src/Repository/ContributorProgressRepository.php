<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\ContributorProgress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContributorProgress>
 */
class ContributorProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContributorProgress::class);
    }

    /**
     * Récupère ou crée la progression d'un contributeur.
     */
    public function findOrCreateForContributor(Contributor $contributor): ContributorProgress
    {
        $progress = $this->findOneBy(['contributor' => $contributor]);

        if (!$progress) {
            $progress = new ContributorProgress();
            $progress->setContributor($contributor);
        }

        return $progress;
    }

    /**
     * Récupère le leaderboard (top contributeurs par XP).
     *
     * @return ContributorProgress[]
     */
    public function getLeaderboard(int $limit = 10): array
    {
        return $this->createQueryBuilder('cp')
            ->leftJoin('cp.contributor', 'c')
            ->where('c.active = :active')
            ->setParameter('active', true)
            ->orderBy('cp.totalXp', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les contributeurs par niveau.
     *
     * @return ContributorProgress[]
     */
    public function findByLevel(int $level): array
    {
        return $this->createQueryBuilder('cp')
            ->leftJoin('cp.contributor', 'c')
            ->where('cp.level = :level')
            ->andWhere('c.active = :active')
            ->setParameter('level', $level)
            ->setParameter('active', true)
            ->orderBy('cp.totalXp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère le rang d'un contributeur.
     */
    public function getRank(Contributor $contributor): int
    {
        $progress = $this->findOneBy(['contributor' => $contributor]);
        if (!$progress) {
            return 0;
        }

        $higherCount = $this->createQueryBuilder('cp')
            ->select('COUNT(cp.id)')
            ->leftJoin('cp.contributor', 'c')
            ->where('cp.totalXp > :xp')
            ->andWhere('c.active = :active')
            ->setParameter('xp', $progress->getTotalXp())
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $higherCount + 1;
    }

    /**
     * Récupère des statistiques globales.
     */
    public function getGlobalStats(): array
    {
        $stats = $this->createQueryBuilder('cp')
            ->select('
                COUNT(cp.id) as total_players,
                AVG(cp.level) as average_level,
                AVG(cp.totalXp) as average_xp,
                MAX(cp.level) as max_level,
                MAX(cp.totalXp) as max_xp
            ')
            ->leftJoin('cp.contributor', 'c')
            ->where('c.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleResult();

        return [
            'total_players' => (int) $stats['total_players'],
            'average_level' => round((float) $stats['average_level'], 1),
            'average_xp'    => (int) $stats['average_xp'],
            'max_level'     => (int) $stats['max_level'],
            'max_xp'        => (int) $stats['max_xp'],
        ];
    }
}

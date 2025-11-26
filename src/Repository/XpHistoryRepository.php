<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\XpHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XpHistory>
 */
class XpHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XpHistory::class);
    }

    /**
     * Récupère l'historique XP d'un contributeur.
     *
     * @return XpHistory[]
     */
    public function findByContributor(Contributor $contributor, int $limit = 50): array
    {
        return $this->createQueryBuilder('xh')
            ->where('xh.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('xh.gainedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère l'historique XP par source.
     *
     * @return XpHistory[]
     */
    public function findBySource(string $source, int $limit = 50): array
    {
        return $this->createQueryBuilder('xh')
            ->where('xh.source = :source')
            ->setParameter('source', $source)
            ->orderBy('xh.gainedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère le total d'XP gagné par un contributeur.
     */
    public function getTotalXpByContributor(Contributor $contributor): int
    {
        $result = $this->createQueryBuilder('xh')
            ->select('SUM(xh.xpAmount)')
            ->where('xh.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Récupère les statistiques d'XP par source.
     */
    public function getXpBySource(Contributor $contributor): array
    {
        $results = $this->createQueryBuilder('xh')
            ->select('xh.source, SUM(xh.xpAmount) as total, COUNT(xh.id) as count')
            ->where('xh.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->groupBy('xh.source')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['source']] = [
                'total' => (int) $result['total'],
                'count' => (int) $result['count'],
            ];
        }

        return $stats;
    }

    /**
     * Récupère l'évolution de l'XP par mois.
     */
    public function getMonthlyXpEvolution(Contributor $contributor, int $year): array
    {
        $results = $this->createQueryBuilder('xh')
            ->select('MONTH(xh.gainedAt) as month, SUM(xh.xpAmount) as total')
            ->where('xh.contributor = :contributor')
            ->andWhere('YEAR(xh.gainedAt) = :year')
            ->setParameter('contributor', $contributor)
            ->setParameter('year', $year)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        $evolution = [];
        foreach ($results as $result) {
            $evolution[(int) $result['month']] = (int) $result['total'];
        }

        return $evolution;
    }
}

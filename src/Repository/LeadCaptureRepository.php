<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LeadCapture;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LeadCapture>
 *
 * @method LeadCapture|null find($id, $lockMode = null, $lockVersion = null)
 * @method LeadCapture|null findOneBy(array $criteria, array $orderBy = null)
 * @method LeadCapture[]    findAll()
 * @method LeadCapture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LeadCaptureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LeadCapture::class);
    }

    /**
     * Trouve un lead par email.
     */
    public function findOneByEmail(string $email): ?LeadCapture
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Compte le nombre de leads par source.
     *
     * @return array<string, int>
     */
    public function countBySource(): array
    {
        $result = $this->createQueryBuilder('lc')
            ->select('lc.source', 'COUNT(lc.id) as total')
            ->groupBy('lc.source')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['source']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Récupère les leads créés dans les derniers X jours.
     *
     * @return LeadCapture[]
     */
    public function findRecentLeads(int $days = 30): array
    {
        $since = new DateTime("-{$days} days");

        return $this->createQueryBuilder('lc')
            ->where('lc.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('lc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les leads qui ont donné leur consentement marketing.
     *
     * @return LeadCapture[]
     */
    public function findWithMarketingConsent(): array
    {
        return $this->createQueryBuilder('lc')
            ->where('lc.marketingConsent = :consent')
            ->setParameter('consent', true)
            ->orderBy('lc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques générales des leads.
     *
     * @return array{total: int, withConsent: int, downloaded: int, avgDownloads: float}
     */
    public function getStats(): array
    {
        $qb = $this->createQueryBuilder('lc');

        $total = (int) $qb->select('COUNT(lc.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $withConsent = (int) $this->createQueryBuilder('lc')
            ->select('COUNT(lc.id)')
            ->where('lc.marketingConsent = :true')
            ->setParameter('true', true)
            ->getQuery()
            ->getSingleScalarResult();

        $downloaded = (int) $this->createQueryBuilder('lc')
            ->select('COUNT(lc.id)')
            ->where('lc.downloadedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $avgDownloads = (float) $this->createQueryBuilder('lc')
            ->select('AVG(lc.downloadCount)')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total'        => $total,
            'withConsent'  => $withConsent,
            'downloaded'   => $downloaded,
            'avgDownloads' => round($avgDownloads, 2),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LeadCapture;
use App\Security\CompanyContext;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<LeadCapture>
 *
 * @method LeadCapture|null find($id, $lockMode = null, $lockVersion = null)
 * @method LeadCapture|null findOneBy(array $criteria, array $orderBy = null)
 * @method LeadCapture[]    findAll()
 * @method LeadCapture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LeadCaptureRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, LeadCapture::class, $companyContext);
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
        $result = $this->createCompanyQueryBuilder('lc')
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

        return $this->createCompanyQueryBuilder('lc')
            ->andWhere('lc.createdAt >= :since')
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
        return $this->createCompanyQueryBuilder('lc')
            ->where('lc.marketingConsent = :consent')
            ->setParameter('consent', true)
            ->orderBy('lc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques générales des leads.
     *
     * @return array{total: int, withConsent: int, downloaded: int, avgDownloads: float, consent_rate: float, download_rate: float, with_marketing_consent: int, avg_downloads: float}
     */
    public function getStats(): array
    {
        $qb = $this->createCompanyQueryBuilder('lc');

        $total = (int) $qb->select('COUNT(lc.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $withConsent = (int) $this->createCompanyQueryBuilder('lc')
            ->select('COUNT(lc.id)')
            ->where('lc.marketingConsent = :true')
            ->setParameter('true', true)
            ->getQuery()
            ->getSingleScalarResult();

        $downloaded = (int) $this->createCompanyQueryBuilder('lc')
            ->select('COUNT(lc.id)')
            ->where('lc.downloadedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $avgDownloads = (float) $this->createCompanyQueryBuilder('lc')
            ->select('AVG(lc.downloadCount)')
            ->getQuery()
            ->getSingleScalarResult();

        // Calcul des taux
        $consentRate  = $total > 0 ? ($withConsent / $total) * 100 : 0;
        $downloadRate = $total > 0 ? ($downloaded / $total)  * 100 : 0;

        return [
            'total'        => $total,
            'withConsent'  => $withConsent,
            'downloaded'   => $downloaded,
            'avgDownloads' => round($avgDownloads, 2),
            // Clés calculées pour les taux
            'consent_rate'  => round($consentRate, 2),
            'download_rate' => round($downloadRate, 2),
            // Alias snake_case pour compatibilité template
            'with_marketing_consent' => $withConsent,
            'avg_downloads'          => round($avgDownloads, 2),
        ];
    }
}

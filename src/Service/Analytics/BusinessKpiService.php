<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\Entity\Invoice;
use App\Entity\Order;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * US-093 (sprint-017 EPIC-002) — Business KPIs pour dashboard PO.
 *
 * 7 KPIs prod :
 *   1. DAU / MAU (Active Users daily / monthly)
 *   2. Projets créés / jour (avg sur 30 derniers jours)
 *   3. Devis signés / mois (current month count)
 *   4. Factures émises / mois (count + total €)
 *   5. Taux conversion devis → projet (%)
 *   6. Revenu trail 30 jours (€ TTC factures payées)
 *   7. Marge moyenne par projet (€ revenu - coût)
 *
 * Cache Redis 5 min via `analytics_cache` pool.
 */
final readonly class BusinessKpiService
{
    private const int CACHE_TTL_SECONDS = 300;

    public function __construct(
        private EntityManagerInterface $em,
        private CompanyContext $companyContext,
        private CacheInterface $analyticsCache,
        private ProjectRepository $projectRepository,
    ) {
    }

    /**
     * Compute all 7 KPIs in one pass (cached).
     *
     * @return array{
     *     dau: int,
     *     mau: int,
     *     projects_per_day: float,
     *     signed_quotes_this_month: int,
     *     invoices_this_month_count: int,
     *     invoices_this_month_amount: float,
     *     conversion_rate_pct: float,
     *     revenue_trail_30d: float,
     *     avg_project_margin: float,
     * }
     */
    public function computeAll(): array
    {
        $companyId = $this->companyContext->getCurrentCompany()->getId();
        $cacheKey = sprintf('business_kpis_company_%d', $companyId ?? 0);

        return $this->analyticsCache->get($cacheKey, function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL_SECONDS);

            return [
                'dau' => $this->dau(),
                'mau' => $this->mau(),
                'projects_per_day' => $this->projectsPerDay(),
                'signed_quotes_this_month' => $this->signedQuotesThisMonth(),
                'invoices_this_month_count' => $this->invoicesThisMonthCount(),
                'invoices_this_month_amount' => $this->invoicesThisMonthAmount(),
                'conversion_rate_pct' => $this->conversionRatePct(),
                'revenue_trail_30d' => $this->revenueTrail30d(),
                'avg_project_margin' => $this->avgProjectMargin(),
            ];
        });
    }

    /**
     * DAU = users actifs sur les 24 dernières heures (last_login_at >= now - 1d).
     */
    public function dau(): int
    {
        $since = new DateTimeImmutable('-1 day');

        return (int) $this->em
            ->createQueryBuilder()
            ->select('COUNT(DISTINCT u.id)')
            ->from(User::class, 'u')
            ->where('u.lastLoginAt >= :since')
            ->andWhere('u.company = :company')
            ->setParameter('since', $since)
            ->setParameter('company', $this->companyContext->getCurrentCompany())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * MAU = users actifs sur les 30 derniers jours.
     */
    public function mau(): int
    {
        $since = new DateTimeImmutable('-30 days');

        return (int) $this->em
            ->createQueryBuilder()
            ->select('COUNT(DISTINCT u.id)')
            ->from(User::class, 'u')
            ->where('u.lastLoginAt >= :since')
            ->andWhere('u.company = :company')
            ->setParameter('since', $since)
            ->setParameter('company', $this->companyContext->getCurrentCompany())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Projets créés / jour = count(Project) sur 30 derniers jours / 30.
     */
    public function projectsPerDay(): float
    {
        $since = new DateTimeImmutable('-30 days');

        // Project has no createdAt column — use startDate as proxy.
        $count = (int) $this->em
            ->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Project::class, 'p')
            ->where('p.startDate >= :since')
            ->andWhere('p.company = :company')
            ->setParameter('since', $since)
            ->setParameter('company', $this->companyContext->getCurrentCompany())
            ->getQuery()
            ->getSingleScalarResult();

        return round($count / 30, 2);
    }

    /**
     * Devis signés ce mois calendaire = count(Order WHERE status='signe' AND createdAt this month).
     */
    public function signedQuotesThisMonth(): int
    {
        $startOfMonth = new DateTimeImmutable('first day of this month 00:00:00');

        return (int) $this->em
            ->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(Order::class, 'o')
            ->where('o.status = :status')
            ->andWhere('o.createdAt >= :since')
            ->andWhere('o.company = :company')
            ->setParameter('status', 'signe')
            ->setParameter('since', $startOfMonth)
            ->setParameter('company', $this->companyContext->getCurrentCompany())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Factures émises ce mois = count(Invoice WHERE status IN ('sent','paid','overdue') AND issuedAt this month).
     */
    public function invoicesThisMonthCount(): int
    {
        $startOfMonth = new DateTimeImmutable('first day of this month 00:00:00');

        return (int) $this->em
            ->createQueryBuilder()
            ->select('COUNT(i.id)')
            ->from(Invoice::class, 'i')
            ->where('i.status IN (:statuses)')
            ->andWhere('i.issuedAt >= :since')
            ->andWhere('i.company = :company')
            ->setParameter('statuses', [Invoice::STATUS_SENT, Invoice::STATUS_PAID, Invoice::STATUS_OVERDUE])
            ->setParameter('since', $startOfMonth)
            ->setParameter('company', $this->companyContext->getCurrentCompany())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Factures émises ce mois — montant total TTC (€).
     */
    public function invoicesThisMonthAmount(): float
    {
        $startOfMonth = new DateTimeImmutable('first day of this month 00:00:00');

        $sum = $this->em
            ->createQueryBuilder()
            ->select('SUM(i.amountTtc)')
            ->from(Invoice::class, 'i')
            ->where('i.status IN (:statuses)')
            ->andWhere('i.issuedAt >= :since')
            ->andWhere('i.company = :company')
            ->setParameter('statuses', [Invoice::STATUS_SENT, Invoice::STATUS_PAID, Invoice::STATUS_OVERDUE])
            ->setParameter('since', $startOfMonth)
            ->setParameter('company', $this->companyContext->getCurrentCompany())
            ->getQuery()
            ->getSingleScalarResult();

        return round((float) ($sum ?? 0), 2);
    }

    /**
     * Taux conversion devis → projet (%) = signed quotes / total quotes (last 30d).
     */
    public function conversionRatePct(): float
    {
        $since = new DateTimeImmutable('-30 days');

        $total = (int) $this->em
            ->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(Order::class, 'o')
            ->where('o.createdAt >= :since')
            ->andWhere('o.company = :company')
            ->setParameter('since', $since)
            ->setParameter('company', $this->companyContext->getCurrentCompany())
            ->getQuery()
            ->getSingleScalarResult();

        if ($total === 0) {
            return 0.0;
        }

        $signed = (int) $this->em
            ->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(Order::class, 'o')
            ->where('o.status = :status')
            ->andWhere('o.createdAt >= :since')
            ->andWhere('o.company = :company')
            ->setParameter('status', 'signe')
            ->setParameter('since', $since)
            ->setParameter('company', $this->companyContext->getCurrentCompany())
            ->getQuery()
            ->getSingleScalarResult();

        return round(($signed / $total) * 100, 2);
    }

    /**
     * Revenu trail 30 jours = SUM(invoices paid amountTtc) sur 30 derniers jours.
     */
    public function revenueTrail30d(): float
    {
        $since = new DateTimeImmutable('-30 days');

        $sum = $this->em
            ->createQueryBuilder()
            ->select('SUM(i.amountTtc)')
            ->from(Invoice::class, 'i')
            ->where('i.status = :status')
            ->andWhere('i.paidAt >= :since')
            ->andWhere('i.company = :company')
            ->setParameter('status', Invoice::STATUS_PAID)
            ->setParameter('since', $since)
            ->setParameter('company', $this->companyContext->getCurrentCompany())
            ->getQuery()
            ->getSingleScalarResult();

        return round((float) ($sum ?? 0), 2);
    }

    /**
     * Marge moyenne par projet (€) — calculée via `ProjectRepository::getAggregatedMetricsFor`.
     *
     * Pour chaque projet actif, somme les marges signées (devis status='signe',
     * 'gagne', 'termine') puis moyenne sur N projets.
     */
    public function avgProjectMargin(): float
    {
        $projectIds = array_map(
            static fn (Project $p): int => $p->id ?? 0,
            $this->em
                ->createQueryBuilder()
                ->select('p')
                ->from(Project::class, 'p')
                ->where('p.status = :status')
                ->andWhere('p.company = :company')
                ->setParameter('status', 'active')
                ->setParameter('company', $this->companyContext->getCurrentCompany())
                ->getQuery()
                ->getResult(),
        );

        $projectIds = array_filter($projectIds, static fn (int $id): bool => $id > 0);

        if ($projectIds === []) {
            return 0.0;
        }

        $metrics = $this->projectRepository->getAggregatedMetricsFor($projectIds);

        $totalMargin = 0.0;
        foreach ($metrics as $row) {
            $totalMargin += (float) ($row['total_margin'] ?? 0);
        }

        return round($totalMargin / count($projectIds), 2);
    }
}

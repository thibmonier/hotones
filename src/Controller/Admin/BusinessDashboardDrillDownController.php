<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Application\Project\Export\KpiDrillDownCsvExporter;
use App\Domain\Project\Repository\BillingLeadTimeReadModelRepositoryInterface;
use App\Domain\Project\Repository\DsoReadModelRepositoryInterface;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Drill-down par client des widgets DSO + lead time (US-116 T-116-02).
 *
 * - GET `/admin/business-dashboard/drill-down/{kpi}` → vue HTML triée
 * - GET `/admin/business-dashboard/drill-down/{kpi}/export` → CSV téléchargeable
 *
 * Fenêtre rolling : `?window=30|90|365` (défaut 30).
 */
#[Route('/admin/business-dashboard/drill-down')]
#[IsGranted('ROLE_ADMIN')]
final class BusinessDashboardDrillDownController extends AbstractController
{
    private const KPI_DSO = 'dso';

    /**
     * @var list<int>
     */
    private const array ALLOWED_WINDOWS = [30, 90, 365];
    private const int DEFAULT_WINDOW_DAYS = 30;

    public function __construct(
        private readonly DsoReadModelRepositoryInterface $dsoRepository,
        private readonly BillingLeadTimeReadModelRepositoryInterface $leadTimeRepository,
        private readonly KpiDrillDownCsvExporter $csvExporter,
    ) {
    }

    #[Route('/{kpi}', name: 'admin_business_dashboard_drill_down', methods: ['GET'], requirements: ['kpi' => 'dso|lead-time'])]
    public function show(string $kpi, Request $request): Response
    {
        $window = $this->resolveWindow($request);

        $aggregates = $this->loadAggregates($kpi, $window);

        return $this->render('admin/dashboard/drill_down.html.twig', [
            'kpi' => $kpi,
            'window' => $window,
            'aggregates' => $aggregates,
            'kpi_label' => $kpi === self::KPI_DSO ? 'DSO (Days Sales Outstanding)' : 'Temps de facturation',
            'value_unit' => 'jours',
        ]);
    }

    #[Route('/{kpi}/export', name: 'admin_business_dashboard_drill_down_export', methods: ['GET'], requirements: ['kpi' => 'dso|lead-time'])]
    public function export(string $kpi, Request $request): Response
    {
        $window = $this->resolveWindow($request);
        $aggregates = $this->loadAggregates($kpi, $window);

        return $this->csvExporter->createResponse($kpi, $window, $aggregates);
    }

    private function resolveWindow(Request $request): int
    {
        $requested = (int) $request->query->get('window', (string) self::DEFAULT_WINDOW_DAYS);

        return in_array($requested, self::ALLOWED_WINDOWS, true) ? $requested : self::DEFAULT_WINDOW_DAYS;
    }

    /**
     * @return list<\App\Domain\Project\Service\ClientDsoAggregate>|list<\App\Domain\Project\Service\ClientBillingLeadTimeAggregate>
     */
    private function loadAggregates(string $kpi, int $window): array
    {
        $now = new DateTimeImmutable();

        return $kpi === self::KPI_DSO
            ? $this->dsoRepository->findAllClientsAggregated($window, $now)
            : $this->leadTimeRepository->findAllClientsAggregated($window, $now);
    }
}

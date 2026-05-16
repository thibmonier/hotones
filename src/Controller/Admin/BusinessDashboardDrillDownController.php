<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Application\Project\Export\KpiDrillDownCsvExporter;
use App\Domain\Project\Repository\BillingLeadTimeReadModelRepositoryInterface;
use App\Domain\Project\Repository\ConversionRateReadModelRepositoryInterface;
use App\Domain\Project\Repository\DsoReadModelRepositoryInterface;
use App\Domain\Project\Repository\MarginAdoptionReadModelRepositoryInterface;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Drill-down par client des widgets DSO + lead time + conversion + margin
 * adoption (US-116 + US-119).
 *
 * - GET `/admin/business-dashboard/drill-down/{kpi}` → vue HTML triée
 * - GET `/admin/business-dashboard/drill-down/{kpi}/export` → CSV téléchargeable
 *
 * Fenêtre rolling : `?window=30|90|365` (défaut 30).
 * Conversion utilise la fenêtre. Margin adoption la voit ignorée
 * (snapshot temporel, pas fenêtre).
 */
#[Route('/admin/business-dashboard/drill-down')]
#[IsGranted('ROLE_ADMIN')]
final class BusinessDashboardDrillDownController extends AbstractController
{
    private const string KPI_DSO = 'dso';
    private const string KPI_LEAD_TIME = 'lead-time';
    private const string KPI_CONVERSION = 'conversion';
    private const string KPI_MARGIN = 'margin';

    /**
     * @var list<int>
     */
    private const array ALLOWED_WINDOWS = [30, 90, 365];
    private const int DEFAULT_WINDOW_DAYS = 30;

    public function __construct(
        private readonly DsoReadModelRepositoryInterface $dsoRepository,
        private readonly BillingLeadTimeReadModelRepositoryInterface $leadTimeRepository,
        private readonly ConversionRateReadModelRepositoryInterface $conversionRepository,
        private readonly MarginAdoptionReadModelRepositoryInterface $marginRepository,
        private readonly KpiDrillDownCsvExporter $csvExporter,
    ) {
    }

    #[Route('/{kpi}', name: 'admin_business_dashboard_drill_down', methods: ['GET'], requirements: ['kpi' => 'dso|lead-time|conversion|margin'])]
    public function show(string $kpi, Request $request): Response
    {
        $window = $this->resolveWindow($request);

        $aggregates = $this->loadAggregates($kpi, $window);

        return $this->render('admin/dashboard/drill_down.html.twig', [
            'kpi' => $kpi,
            'window' => $window,
            'aggregates' => $aggregates,
            'kpi_label' => $this->labelFor($kpi),
            'value_unit' => $this->unitFor($kpi),
        ]);
    }

    #[Route('/{kpi}/export', name: 'admin_business_dashboard_drill_down_export', methods: ['GET'], requirements: ['kpi' => 'dso|lead-time|conversion|margin'])]
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
     * @return list<\App\Domain\Project\Service\ClientDsoAggregate>|list<\App\Domain\Project\Service\ClientBillingLeadTimeAggregate>|list<\App\Domain\Project\Service\ClientConversionAggregate>|list<\App\Domain\Project\Service\ClientMarginAdoptionAggregate>
     */
    private function loadAggregates(string $kpi, int $window): array
    {
        $now = new DateTimeImmutable();

        return match ($kpi) {
            self::KPI_DSO => $this->dsoRepository->findAllClientsAggregated($window, $now),
            self::KPI_LEAD_TIME => $this->leadTimeRepository->findAllClientsAggregated($window, $now),
            self::KPI_CONVERSION => $this->conversionRepository->findAllClientsAggregated($window, $now),
            self::KPI_MARGIN => $this->marginRepository->findAllClientsAggregated($window, $now),
            default => [],
        };
    }

    private function labelFor(string $kpi): string
    {
        return match ($kpi) {
            self::KPI_DSO => 'DSO (Days Sales Outstanding)',
            self::KPI_LEAD_TIME => 'Temps de facturation',
            self::KPI_CONVERSION => 'Taux de conversion devis → commande',
            self::KPI_MARGIN => 'Adoption marge (% projets fresh)',
            default => $kpi,
        };
    }

    private function unitFor(string $kpi): string
    {
        return match ($kpi) {
            self::KPI_DSO, self::KPI_LEAD_TIME => 'jours',
            self::KPI_CONVERSION, self::KPI_MARGIN => '%',
            default => '',
        };
    }
}

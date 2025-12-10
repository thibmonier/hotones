<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\GenerateForecastsMessage;
use App\Repository\FactForecastRepository;
use App\Service\ForecastingService;
use DateTimeImmutable;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/analytics/forecasting')]
#[IsGranted('ROLE_MANAGER')]
class ForecastingController extends AbstractController
{
    public function __construct(
        private readonly ForecastingService $forecastingService,
        private readonly FactForecastRepository $forecastRepository,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    #[Route('', name: 'forecasting_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $months = (int) $request->query->get('months', 12);

        if (!in_array($months, [3, 6, 12], true)) {
            $months = 12;
        }

        // Get forecast date range
        $now       = new DateTimeImmutable();
        $startDate = $now->modify('first day of this month');
        $endDate   = $now->modify("+{$months} months")->modify('last day of this month');

        // Get forecasts from database
        $realisticForecasts   = $this->forecastRepository->findByDateRange($startDate, $endDate, 'realistic');
        $optimisticForecasts  = $this->forecastRepository->findByDateRange($startDate, $endDate, 'optimistic');
        $pessimisticForecasts = $this->forecastRepository->findByDateRange($startDate, $endDate, 'pessimistic');

        // Calculate average accuracy for realistic scenario
        $averageAccuracy = $this->forecastRepository->calculateAverageAccuracy('realistic', 6);

        // Prepare chart data
        $chartData = $this->prepareChartData($realisticForecasts, $optimisticForecasts, $pessimisticForecasts);

        // Get legacy forecast for comparison (using old method)
        try {
            $legacyForecast = $this->forecastingService->forecastRevenue($months);
        } catch (RuntimeException $e) {
            $legacyForecast = null;
        }

        return $this->render('forecasting/index.html.twig', [
            'months'                => $months,
            'realistic_forecasts'   => $realisticForecasts,
            'optimistic_forecasts'  => $optimisticForecasts,
            'pessimistic_forecasts' => $pessimisticForecasts,
            'chart_data'            => $chartData,
            'average_accuracy'      => $averageAccuracy,
            'legacy_forecast'       => $legacyForecast,
        ]);
    }

    #[Route('/dashboard', name: 'forecasting_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        $horizon = (int) $request->query->get('horizon', 12);

        if (!in_array($horizon, [3, 6, 12], true)) {
            $horizon = 12;
        }

        try {
            $forecast = $this->forecastingService->forecastRevenue($horizon);
        } catch (RuntimeException $e) {
            $this->addFlash('error', 'Impossible de générer les prévisions : '.$e->getMessage());
            $forecast = null;
        }

        return $this->render('forecasting/dashboard_legacy.html.twig', [
            'forecast' => $forecast,
            'horizon'  => $horizon,
        ]);
    }

    #[Route('/generate', name: 'forecasting_generate', methods: ['POST'])]
    public function generate(Request $request): Response
    {
        $months = (int) $request->request->get('months', 12);

        if (!in_array($months, [3, 6, 12], true)) {
            $months = 12;
        }

        try {
            // Dispatch async message for forecast generation
            $this->messageBus->dispatch(new GenerateForecastsMessage($months));

            $this->addFlash('info', sprintf(
                'La génération des prévisions pour %d mois a été lancée en arrière-plan. Rechargez la page dans quelques instants.',
                $months,
            ));
        } catch (Exception $e) {
            $this->addFlash('error', 'Erreur lors du lancement de la génération : '.$e->getMessage());
        }

        return $this->redirectToRoute('forecasting_index', ['months' => $months]);
    }

    /**
     * Prepare data for Chart.js.
     */
    private function prepareChartData(array $realistic, array $optimistic, array $pessimistic): array
    {
        $labels          = [];
        $realisticData   = [];
        $optimisticData  = [];
        $pessimisticData = [];
        $confidenceMin   = [];
        $confidenceMax   = [];

        foreach ($realistic as $forecast) {
            $labels[]        = $forecast->getPeriodStart()->format('M Y');
            $realisticData[] = (float) $forecast->getPredictedRevenue();
            $confidenceMin[] = (float) $forecast->getConfidenceMin();
            $confidenceMax[] = (float) $forecast->getConfidenceMax();
        }

        foreach ($optimistic as $forecast) {
            $optimisticData[] = (float) $forecast->getPredictedRevenue();
        }

        foreach ($pessimistic as $forecast) {
            $pessimisticData[] = (float) $forecast->getPredictedRevenue();
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Scénario Réaliste',
                    'data'            => $realisticData,
                    'borderColor'     => 'rgb(54, 162, 235)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Scénario Optimiste',
                    'data'            => $optimisticData,
                    'borderColor'     => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.1)',
                    'borderDash'      => [5, 5],
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Scénario Pessimiste',
                    'data'            => $pessimisticData,
                    'borderColor'     => 'rgb(255, 99, 132)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.1)',
                    'borderDash'      => [5, 5],
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Intervalle de confiance (min)',
                    'data'            => $confidenceMin,
                    'borderColor'     => 'rgba(201, 203, 207, 0.5)',
                    'backgroundColor' => 'rgba(201, 203, 207, 0.1)',
                    'borderDash'      => [2, 2],
                    'pointRadius'     => 0,
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Intervalle de confiance (max)',
                    'data'            => $confidenceMax,
                    'borderColor'     => 'rgba(201, 203, 207, 0.5)',
                    'backgroundColor' => 'rgba(201, 203, 207, 0.1)',
                    'borderDash'      => [2, 2],
                    'pointRadius'     => 0,
                    'tension'         => 0.4,
                    'fill'            => '-1',
                ],
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\Message\RecalculateMetricsMessage;
use App\Service\MetricsCalculationService;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/analytics')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private MetricsCalculationService $metricsService,
        private MessageBusInterface $messageBus
    ) {
    }

    #[Route('/dashboard', name: 'analytics_dashboard', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupérer la période depuis l'URL en priorité
        $period = $request->query->get('period');

        // Si pas de période dans l'URL, utiliser la session ou 'month' par défaut
        if ($period === null) {
            $session = $request->getSession();
            $period  = $session->get('dashboard_period', 'month');
        } else {
            // Si période fournie dans l'URL, la sauvegarder en session
            $request->getSession()->set('dashboard_period', $period);
        }

        $customStart = $request->query->get('start_date');
        $customEnd   = $request->query->get('end_date');

        // Calculer les dates selon la période sélectionnée
        [$startDate, $endDate] = $this->calculatePeriodDates($period, $customStart, $customEnd);

        // Calculer les KPIs
        $kpis = $this->metricsService->calculateKPIs($startDate, $endDate);

        // Calculer l'évolution mensuelle pour les graphiques
        $monthlyEvolution = $this->metricsService->calculateMonthlyEvolution(12);

        return $this->render('analytics/dashboard.html.twig', [
            'kpis'              => $kpis,
            'monthly_evolution' => $monthlyEvolution,
            'selected_period'   => $period,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
        ]);
    }

    /**
     * Calcule les dates de début et fin selon la période sélectionnée.
     */
    private function calculatePeriodDates(string $period, ?string $customStart, ?string $customEnd): array
    {
        $now = new DateTime();

        return match ($period) {
            'today' => [
                new DateTime('today'),
                new DateTime('today'),
            ],
            'week' => [
                (clone $now)->modify('monday this week'),
                (clone $now)->modify('sunday this week'),
            ],
            'month' => [
                new DateTime('first day of this month'),
                new DateTime('last day of this month'),
            ],
            'quarter' => [
                $this->getQuarterStart($now),
                $this->getQuarterEnd($now),
            ],
            'year' => [
                new DateTime('first day of January this year'),
                new DateTime('last day of December this year'),
            ],
            'custom' => [
                $customStart ? new DateTime($customStart) : new DateTime('first day of this month'),
                $customEnd ? new DateTime($customEnd) : new DateTime('last day of this month'),
            ],
            default => [
                new DateTime('first day of this month'),
                new DateTime('last day of this month'),
            ],
        };
    }

    /**
     * Retourne le premier jour du trimestre en cours.
     */
    private function getQuarterStart(DateTime $date): DateTime
    {
        $month             = (int) $date->format('n');
        $quarterStartMonth = (int) (floor(($month - 1) / 3) * 3) + 1;

        return (clone $date)->setDate(
            (int) $date->format('Y'),
            $quarterStartMonth,
            1,
        )->setTime(0, 0);
    }

    /**
     * Retourne le dernier jour du trimestre en cours.
     */
    private function getQuarterEnd(DateTime $date): DateTime
    {
        $quarterStart = $this->getQuarterStart($date);

        return (clone $quarterStart)->modify('+3 months')->modify('-1 day')->setTime(23, 59, 59);
    }

    #[Route('/recalculate', name: 'analytics_recalculate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function recalculate(Request $request): JsonResponse
    {
        $period      = $request->request->get('period', date('Y'));
        $granularity = $request->request->get('granularity', 'monthly');

        try {
            // Dispatch le message de recalcul
            $this->messageBus->dispatch(
                new RecalculateMetricsMessage($period, $granularity),
            );

            $this->addFlash('success', 'Recalcul des métriques lancé en arrière-plan.');

            return new JsonResponse([
                'success' => true,
                'message' => 'Recalcul des métriques lancé en arrière-plan.',
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\TreasuryService;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/treasury')]
#[IsGranted('ROLE_COMPTA')]
class TreasuryController extends AbstractController
{
    #[Route('/dashboard', name: 'treasury_dashboard', methods: ['GET'])]
    public function dashboard(Request $request, TreasuryService $treasuryService): Response
    {
        // Filtres de période
        $period = $request->query->get('period', 'year');

        $startDate = null;
        $endDate   = new DateTime();

        switch ($period) {
            case 'month':
                $startDate = (clone $endDate)->modify('first day of this month');
                break;
            case 'quarter':
                $currentMonth      = (int) $endDate->format('n');
                $quarterStartMonth = ((int) (($currentMonth - 1) / 3)) * 3 + 1;
                $startDate         = (clone $endDate)->setDate(
                    (int) $endDate->format('Y'),
                    $quarterStartMonth,
                    1,
                );
                break;
            case 'year':
                $startDate = (clone $endDate)->modify('first day of january this year');
                break;
            case 'all':
            default:
                $startDate = null;
                break;
        }

        // KPIs principaux
        $kpis = $treasuryService->getMainKpis($startDate, $endDate);

        // Prévisionnel 90 jours
        $forecast = $treasuryService->getForecast(90);

        // Factures en retard
        $overdueInvoices = $treasuryService->getOverdueInvoices();

        // Top clients
        $topClients = $treasuryService->getClientStats(10);

        // Évolution mensuelle
        $monthlyTrend = $treasuryService->getMonthlyTrend(12);

        return $this->render('treasury/dashboard.html.twig', [
            'kpis'            => $kpis,
            'forecast'        => $forecast,
            'overdueInvoices' => $overdueInvoices,
            'topClients'      => $topClients,
            'monthlyTrend'    => $monthlyTrend,
            'period'          => $period,
        ]);
    }
}

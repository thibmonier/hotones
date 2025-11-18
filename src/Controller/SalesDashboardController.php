<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sales-dashboard')]
#[IsGranted('ROLE_CHEF_PROJET')]
class SalesDashboardController extends AbstractController
{
    #[Route('', name: 'sales_dashboard_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $orderRepository = $em->getRepository(Order::class);
        $userRepository  = $em->getRepository(User::class);

        // Période de filtrage (par défaut: année en cours)
        $year      = $request->query->get('year', date('Y'));
        $startDate = new DateTime("$year-01-01");
        $endDate   = new DateTime("$year-12-31");

        // Filtres utilisateur
        $filterUserId   = $request->query->get('user_id') ? (int) $request->query->get('user_id') : null;
        $filterUserRole = $request->query->get('user_role') ?: null; // 'commercial' ou 'chef_projet'

        // KPI 1: Nombre de devis en attente de signature
        $pendingCount = $orderRepository->countByStatus(OrderStatus::PENDING->value, $filterUserId, $filterUserRole);

        // KPI 2: CA signé sur la période
        $signedRevenue = $orderRepository->getSignedRevenueForPeriod($startDate, $endDate, $filterUserId, $filterUserRole);

        // KPI 3: Taux de conversion
        $conversionRate = $orderRepository->getConversionRate($startDate, $endDate, $filterUserId, $filterUserRole);

        // KPI 4: Évolution du CA signé (mensuelle)
        $revenueEvolution = $orderRepository->getRevenueEvolution($startDate, $endDate);

        // KPI 4: Somme de CA par statut
        $statsByStatus = $orderRepository->getStatsByStatus($filterUserId, $filterUserRole);

        // Complément: Enrichir avec les labels et les devis manquants
        $statsWithLabels = [];
        foreach (OrderStatus::cases() as $status) {
            $statusValue       = $status->value;
            $statsWithLabels[] = [
                'status' => $statusValue,
                'label'  => $status->getLabel(),
                'count'  => $statsByStatus[$statusValue]['count'] ?? 0,
                'total'  => $statsByStatus[$statusValue]['total'] ?? 0.0,
            ];
        }

        // Devis récents
        $recentOrders = $orderRepository->getRecentOrders(5);

        // Préparer les données pour le graphique d'évolution
        $evolutionData = $this->prepareEvolutionChartData($revenueEvolution, $startDate, $endDate);

        // Années disponibles pour le filtre
        $availableYears = $this->getAvailableYears($em);

        // Comparaison avec l'année précédente
        $yearComparison = null;
        if ($year > min($availableYears)) {
            $previousYear   = (int) $year - 1;
            $yearComparison = $orderRepository->getYearComparison((int) $year, $previousYear, $filterUserId, $filterUserRole);
        }

        // Liste des utilisateurs pour les filtres
        $users = $userRepository->findAll();

        return $this->render('sales_dashboard/index.html.twig', [
            'pendingCount'     => $pendingCount,
            'signedRevenue'    => $signedRevenue,
            'conversionRate'   => $conversionRate,
            'revenueEvolution' => $revenueEvolution,
            'statsByStatus'    => $statsWithLabels,
            'recentOrders'     => $recentOrders,
            'evolutionData'    => $evolutionData,
            'selectedYear'     => $year,
            'availableYears'   => $availableYears,
            'yearComparison'   => $yearComparison,
            'users'            => $users,
            'filterUserId'     => $filterUserId,
            'filterUserRole'   => $filterUserRole,
        ]);
    }

    /**
     * Prépare les données pour le graphique d'évolution mensuelle.
     */
    private function prepareEvolutionChartData(array $revenueEvolution, DateTime $startDate, DateTime $endDate): array
    {
        $labels = [];
        $data   = [];

        $currentMonth = clone $startDate;
        while ($currentMonth <= $endDate) {
            $monthKey = $currentMonth->format('Y-m');
            $labels[] = $currentMonth->format('M Y');
            $data[]   = $revenueEvolution[$monthKey] ?? 0.0;
            $currentMonth->modify('+1 month');
        }

        return [
            'labels' => $labels,
            'data'   => $data,
        ];
    }

    /**
     * Récupère les années disponibles basées sur les devis existants.
     */
    private function getAvailableYears(EntityManagerInterface $em): array
    {
        $conn   = $em->getConnection();
        $sql    = 'SELECT DISTINCT YEAR(created_at) as year FROM orders ORDER BY year DESC';
        $result = $conn->executeQuery($sql)->fetchAllAssociative();

        $years = array_map(fn ($row) => (int) $row['year'], $result);

        // Ajouter l'année en cours si elle n'existe pas
        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years, true)) {
            $years[] = $currentYear;
            rsort($years);
        }

        return $years;
    }

    #[Route('/export-pdf', name: 'sales_dashboard_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, EntityManagerInterface $em): Response
    {
        $orderRepository = $em->getRepository(Order::class);

        // Récupération des mêmes paramètres que le dashboard
        $year      = $request->query->get('year', date('Y'));
        $startDate = new DateTime("$year-01-01");
        $endDate   = new DateTime("$year-12-31");

        $filterUserId   = $request->query->get('user_id') ? (int) $request->query->get('user_id') : null;
        $filterUserRole = $request->query->get('user_role') ?: null;

        // Récupération des données
        $pendingCount   = $orderRepository->countByStatus(OrderStatus::PENDING->value, $filterUserId, $filterUserRole);
        $signedRevenue  = $orderRepository->getSignedRevenueForPeriod($startDate, $endDate, $filterUserId, $filterUserRole);
        $conversionRate = $orderRepository->getConversionRate($startDate, $endDate, $filterUserId, $filterUserRole);
        $statsByStatus  = $orderRepository->getStatsByStatus($filterUserId, $filterUserRole);

        // Enrichir avec les labels
        $statsWithLabels = [];
        foreach (OrderStatus::cases() as $status) {
            $statusValue       = $status->value;
            $statsWithLabels[] = [
                'status' => $statusValue,
                'label'  => $status->getLabel(),
                'count'  => $statsByStatus[$statusValue]['count'] ?? 0,
                'total'  => $statsByStatus[$statusValue]['total'] ?? 0.0,
            ];
        }

        // Comparaison annuelle
        $availableYears = $this->getAvailableYears($em);
        $yearComparison = null;
        if ($year > min($availableYears)) {
            $previousYear   = (int) $year - 1;
            $yearComparison = $orderRepository->getYearComparison((int) $year, $previousYear, $filterUserId, $filterUserRole);
        }

        // Générer le HTML
        $html = $this->renderView('sales_dashboard/pdf.html.twig', [
            'pendingCount'   => $pendingCount,
            'signedRevenue'  => $signedRevenue,
            'conversionRate' => $conversionRate,
            'statsByStatus'  => $statsWithLabels,
            'selectedYear'   => $year,
            'yearComparison' => $yearComparison,
            'generatedAt'    => new DateTime(),
        ]);

        // Configuration de Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Retourner le PDF
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="dashboard-commercial-%s.pdf"', $year),
            ],
        );
    }
}

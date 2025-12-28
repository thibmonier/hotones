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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/sales-dashboard')]
#[IsGranted('ROLE_CHEF_PROJET')]
class SalesDashboardController extends AbstractController
{
    #[Route('', name: 'sales_dashboard_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, CacheInterface $cache): Response
    {
        $orderRepository = $em->getRepository(Order::class);
        $userRepository  = $em->getRepository(User::class);

        // Période de filtrage (par défaut: année en cours)
        $year      = $request->query->get('year', date('Y'));
        $startDate = new DateTime("$year-01-01");
        $endDate   = new DateTime("$year-12-31");

        // Filtres utilisateur - gestion robuste des paramètres
        $userIdParam    = $request->query->get('user_id');
        $filterUserId   = ($userIdParam !== null && $userIdParam !== '') ? (int) $userIdParam : null;
        $userRoleParam  = $request->query->get('user_role');
        $filterUserRole = ($userRoleParam !== null && $userRoleParam !== '') ? $userRoleParam : null;

        // Créer une clé de cache basée sur les filtres
        $cacheKey = sprintf(
            'sales_dashboard_%s_%s_%s_%s',
            $year,
            $filterUserId   ?? 'all',
            $filterUserRole ?? 'all',
            $startDate->format('Y-m-d'),
        );

        // KPI 1: Nombre de devis en attente de signature (avec cache)
        $pendingCount = $cache->get($cacheKey.'_pending', function (ItemInterface $item) use ($orderRepository, $filterUserId, $filterUserRole) {
            $item->expiresAfter(900); // 15 minutes

            return $orderRepository->countByStatus(OrderStatus::PENDING->value, $filterUserId, $filterUserRole);
        });

        // KPI 2: CA signé sur la période (avec cache)
        $signedRevenue = $cache->get($cacheKey.'_signed', function (ItemInterface $item) use ($orderRepository, $startDate, $endDate, $filterUserId, $filterUserRole) {
            $item->expiresAfter(900); // 15 minutes

            return $orderRepository->getSignedRevenueForPeriod($startDate, $endDate, $filterUserId, $filterUserRole);
        });

        // KPI 3: Taux de conversion (avec cache)
        $conversionRate = $cache->get($cacheKey.'_conversion', function (ItemInterface $item) use ($orderRepository, $startDate, $endDate, $filterUserId, $filterUserRole) {
            $item->expiresAfter(900); // 15 minutes

            return $orderRepository->getConversionRate($startDate, $endDate, $filterUserId, $filterUserRole);
        });

        // KPI 4: Évolution du CA signé (mensuelle) (avec cache)
        $revenueEvolution = $cache->get($cacheKey.'_evolution', function (ItemInterface $item) use ($orderRepository, $startDate, $endDate) {
            $item->expiresAfter(1800); // 30 minutes

            return $orderRepository->getRevenueEvolution($startDate, $endDate);
        });

        // KPI 5: Évolution du volume de devis créés (mensuel) (avec cache)
        $volumeEvolution = $cache->get($cacheKey.'_volume', function (ItemInterface $item) use ($orderRepository, $startDate, $endDate) {
            $item->expiresAfter(1800); // 30 minutes

            return $orderRepository->getCreatedOrdersVolumeEvolution($startDate, $endDate);
        });

        // KPI 6: Somme de CA par statut (filtrée par période) (avec cache)
        $statsByStatus = $cache->get($cacheKey.'_stats', function (ItemInterface $item) use ($orderRepository, $startDate, $endDate, $filterUserId, $filterUserRole) {
            $item->expiresAfter(900); // 15 minutes

            return $orderRepository->getStatsByStatus($startDate, $endDate, $filterUserId, $filterUserRole);
        });

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
        $evolutionData = $this->prepareEvolutionChartData($revenueEvolution, $volumeEvolution, $startDate, $endDate);

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
    private function prepareEvolutionChartData(array $revenueEvolution, array $volumeEvolution, DateTime $startDate, DateTime $endDate): array
    {
        $labels      = [];
        $revenueData = [];
        $volumeData  = [];

        $currentMonth = clone $startDate;
        while ($currentMonth <= $endDate) {
            $monthKey = $currentMonth->format('Y-m');
            $labels[] = $currentMonth->format('M Y');

            $revenueData[] = $revenueEvolution[$monthKey] ?? 0.0;
            $volumeData[]  = $volumeEvolution[$monthKey]  ?? 0;

            $currentMonth->modify('+1 month');
        }

        return [
            'labels'       => $labels,
            'revenue_data' => $revenueData,
            'volume_data'  => $volumeData,
        ];
    }

    /**
     * Récupère les années disponibles basées sur les devis existants.
     */
    private function getAvailableYears(EntityManagerInterface $em): array
    {
        $conn = $em->getConnection();

        // Support both MySQL and PostgreSQL
        $platform    = $conn->getDatabasePlatform();
        $yearExtract = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform
            ? 'EXTRACT(YEAR FROM created_at)'
            : 'YEAR(created_at)'; // MySQL/MariaDB

        $sql    = "SELECT DISTINCT {$yearExtract} as year FROM orders ORDER BY year DESC";
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

        $userIdParam    = $request->query->get('user_id');
        $filterUserId   = ($userIdParam !== null && $userIdParam !== '') ? (int) $userIdParam : null;
        $userRoleParam  = $request->query->get('user_role');
        $filterUserRole = ($userRoleParam !== null && $userRoleParam !== '') ? $userRoleParam : null;

        // Récupération des données
        $pendingCount   = $orderRepository->countByStatus(OrderStatus::PENDING->value, $filterUserId, $filterUserRole);
        $signedRevenue  = $orderRepository->getSignedRevenueForPeriod($startDate, $endDate, $filterUserId, $filterUserRole);
        $conversionRate = $orderRepository->getConversionRate($startDate, $endDate, $filterUserId, $filterUserRole);
        $statsByStatus  = $orderRepository->getStatsByStatus($startDate, $endDate, $filterUserId, $filterUserRole);

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

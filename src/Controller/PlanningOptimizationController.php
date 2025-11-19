<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Planning\AI\PlanningAIAssistant;
use App\Service\Planning\PlanningOptimizer;
use App\Service\Planning\TaceAnalyzer;

use function count;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/planning/optimization')]
#[IsGranted('ROLE_MANAGER')]
class PlanningOptimizationController extends AbstractController
{
    public function __construct(
        private readonly PlanningOptimizer $optimizer,
        private readonly TaceAnalyzer $taceAnalyzer,
        private readonly PlanningAIAssistant $aiAssistant
    ) {
    }

    #[Route('', name: 'planning_optimization_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupérer les paramètres de période
        $startDate = $request->query->get('start_date')
            ? new DateTime($request->query->get('start_date'))
            : new DateTime('first day of this month');

        $endDate = $request->query->get('end_date')
            ? new DateTime($request->query->get('end_date'))
            : new DateTime('last day of next month');

        // Générer les recommandations
        $result = $this->optimizer->generateRecommendations($startDate, $endDate);

        // Enrichir avec l'IA si disponible
        $aiEnhanced = null;
        if ($this->aiAssistant->isEnabled()) {
            $aiEnhanced = $this->aiAssistant->enhanceRecommendations([
                'analysis'        => $result['analysis'],
                'recommendations' => $result['recommendations'],
                'projects'        => [], // TODO: Ajouter les projets actifs
            ]);
        }

        return $this->render('planning_optimization/index.html.twig', [
            'recommendations' => $result['recommendations'],
            'analysis'        => $result['analysis'],
            'summary'         => $result['summary'],
            'period'          => $result['period'],
            'thresholds'      => $this->taceAnalyzer->getThresholds(),
            'ai_enhanced'     => $aiEnhanced,
        ]);
    }

    #[Route('/dashboard', name: 'planning_optimization_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $startDate = new DateTime('first day of this month');
        $endDate   = new DateTime('last day of next month');

        // Analyse rapide pour le dashboard
        $analysis = $this->taceAnalyzer->analyzeAllContributors($startDate, $endDate);
        $result   = $this->optimizer->generateRecommendations($startDate, $endDate);

        return $this->render('planning_optimization/dashboard.html.twig', [
            'critical_count'      => count($analysis['critical']),
            'overloaded_count'    => count($analysis['overloaded']),
            'underutilized_count' => count($analysis['underutilized']),
            'optimal_count'       => count($analysis['optimal']),
            'top_recommendations' => array_slice($result['recommendations'], 0, 5),
            'summary'             => $result['summary'],
        ]);
    }

    #[Route('/api/recommendations', name: 'planning_optimization_api', methods: ['GET'])]
    public function apiRecommendations(Request $request): Response
    {
        $startDate = $request->query->get('start')
            ? new DateTime($request->query->get('start'))
            : new DateTime('first day of this month');

        $endDate = $request->query->get('end')
            ? new DateTime($request->query->get('end'))
            : new DateTime('last day of next month');

        $result = $this->optimizer->generateRecommendations($startDate, $endDate);

        return $this->json([
            'success'         => true,
            'recommendations' => array_map(function ($rec) {
                return [
                    'type'           => $rec['type'],
                    'title'          => $rec['title'],
                    'description'    => $rec['description'],
                    'priority_score' => $rec['priority_score'],
                    'severity'       => $rec['severity_level'],
                    'impact'         => $rec['expected_impact'] ?? null,
                    'contributor'    => $rec['contributor']->getFullName(),
                    'target'         => $rec['target']?->getFullName() ?? null,
                    'project'        => $rec['project']?->getName()    ?? null,
                ];
            }, $result['recommendations']),
            'summary' => $result['summary'],
        ]);
    }
}

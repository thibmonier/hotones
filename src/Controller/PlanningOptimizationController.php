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
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/planning/optimization')]
#[IsGranted('ROLE_MANAGER')]
class PlanningOptimizationController extends AbstractController
{
    public function __construct(
        private readonly PlanningOptimizer $optimizer,
        private readonly TaceAnalyzer $taceAnalyzer,
        private readonly PlanningAIAssistant $aiAssistant,
        private readonly CacheInterface $cache
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

        // Créer une clé de cache basée sur la période
        $cacheKey = sprintf(
            'planning_optimization_%s_%s',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        );

        // Vérifier si les données sont en cache
        $fromCache = false;
        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            $fromCache = true;
            $result    = $cacheItem->get();
        } else {
            $result = $this->optimizer->generateRecommendations($startDate, $endDate);
            $cacheItem->set($result);
            $cacheItem->expiresAfter(3600); // Cache valide pendant 1 heure
            $this->cache->save($cacheItem);
        }

        // Enrichir avec l'IA si disponible (toujours en cache séparé car plus coûteux)
        $aiEnhanced = null;
        if ($this->aiAssistant->isEnabled()) {
            $aiCacheKey = $cacheKey.'_ai';
            $aiEnhanced = $this->cache->get($aiCacheKey, function (ItemInterface $item) use ($result) {
                // Cache IA valide pendant 2 heures
                $item->expiresAfter(7200);

                return $this->aiAssistant->enhanceRecommendations([
                    'analysis'        => $result['analysis'],
                    'recommendations' => $result['recommendations'],
                    'projects'        => [], // TODO: Ajouter les projets actifs
                ]);
            });
        }

        return $this->render('planning_optimization/index.html.twig', [
            'recommendations' => $result['recommendations'],
            'analysis'        => $result['analysis'],
            'summary'         => $result['summary'],
            'period'          => $result['period'],
            'thresholds'      => $this->taceAnalyzer->getThresholds(),
            'ai_enhanced'     => $aiEnhanced,
            'from_cache'      => $fromCache,
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

    #[Route('/apply', name: 'planning_optimization_apply', methods: ['POST'])]
    public function applyRecommendation(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $index = $data['index'] ?? null;
        if ($index === null) {
            return $this->json([
                'success' => false,
                'message' => 'Index de recommandation manquant',
            ], 400);
        }

        // Récupérer les paramètres de période
        $startDate = isset($data['start_date'])
            ? new DateTime($data['start_date'])
            : new DateTime('first day of this month');

        $endDate = isset($data['end_date'])
            ? new DateTime($data['end_date'])
            : new DateTime('last day of next month');

        // Régénérer les recommandations pour obtenir celle à l'index demandé
        $result = $this->optimizer->generateRecommendations($startDate, $endDate);

        if (!isset($result['recommendations'][$index])) {
            return $this->json([
                'success' => false,
                'message' => 'Recommandation non trouvée à l\'index '.$index,
            ], 404);
        }

        $recommendation = $result['recommendations'][$index];

        // Appliquer la recommandation
        $applyResult = $this->optimizer->applyRecommendation($recommendation, $startDate, $endDate);

        if ($applyResult['success']) {
            $this->addFlash('success', $applyResult['message']);
        } else {
            $this->addFlash('error', $applyResult['message']);
        }

        return $this->json($applyResult);
    }

    #[Route('/clear-cache', name: 'planning_optimization_clear_cache', methods: ['POST'])]
    public function clearCache(Request $request): Response
    {
        // Récupérer les paramètres de période
        $startDate = $request->request->get('start_date')
            ? new DateTime($request->request->get('start_date'))
            : new DateTime('first day of this month');

        $endDate = $request->request->get('end_date')
            ? new DateTime($request->request->get('end_date'))
            : new DateTime('last day of next month');

        // Créer la clé de cache
        $cacheKey = sprintf(
            'planning_optimization_%s_%s',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        );

        // Supprimer le cache
        $this->cache->delete($cacheKey);
        $this->cache->delete($cacheKey.'_ai');

        $this->addFlash('success', 'Le cache a été vidé avec succès.');

        // Rediriger vers la page d\'optimisation
        return $this->redirectToRoute('planning_optimization_index', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date'   => $endDate->format('Y-m-d'),
        ]);
    }
}

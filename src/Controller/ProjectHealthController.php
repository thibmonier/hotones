<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProjectHealthScoreRepository;
use App\Service\ProjectRiskAnalyzer;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projects')]
#[IsGranted('ROLE_CHEF_PROJET')]
class ProjectHealthController extends AbstractController
{
    public function __construct(
        private readonly ProjectHealthScoreRepository $healthScoreRepository,
        private readonly ProjectRiskAnalyzer $riskAnalyzer,
    ) {
    }

    #[Route('/at-risk', name: 'projects_at_risk', methods: ['GET'])]
    public function atRisk(): Response
    {
        // Get all at-risk projects (warning or critical)
        $atRiskProjects = $this->healthScoreRepository->findProjectsAtRisk();

        // Get health level counts for stats
        $counts = $this->healthScoreRepository->countByHealthLevel();

        return $this->render('project_health/at_risk.html.twig', [
            'at_risk_projects' => $atRiskProjects,
            'counts'           => $counts,
        ]);
    }

    #[Route('/health-overview', name: 'projects_health_overview', methods: ['GET'])]
    public function healthOverview(): Response
    {
        // Get health level counts
        $counts = $this->healthScoreRepository->countByHealthLevel();

        // Get critical projects (most urgent)
        $criticalProjects = [];
        $allAtRisk        = $this->healthScoreRepository->findProjectsAtRisk();

        foreach ($allAtRisk as $healthScore) {
            if ($healthScore->getHealthLevel() === 'critical') {
                $criticalProjects[] = $healthScore;
            }
        }

        return $this->render('project_health/overview.html.twig', [
            'counts'            => $counts,
            'critical_projects' => $criticalProjects,
        ]);
    }

    #[Route('/refresh-health', name: 'projects_refresh_health', methods: ['POST'])]
    public function refreshHealth(): Response
    {
        try {
            $healthScores = $this->riskAnalyzer->analyzeAllActiveProjects();
            $this->addFlash('success', sprintf('%d projets analysés avec succès', count($healthScores)));
        } catch (Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'analyse : '.$e->getMessage());
        }

        return $this->redirectToRoute('projects_at_risk');
    }
}

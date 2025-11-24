<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\HrMetricsService;
use App\Service\SkillGapAnalyzer;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/hr')]
#[IsGranted('ROLE_MANAGER')]
class HrDashboardController extends AbstractController
{
    public function __construct(
        private HrMetricsService $hrMetricsService,
        private SkillGapAnalyzer $skillGapAnalyzer
    ) {
    }

    #[Route('/dashboard', name: 'hr_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        // Période par défaut : année en cours
        $year      = (int) ($request->query->get('year') ?: date('Y'));
        $startDate = new DateTime($year.'-01-01');
        $endDate   = new DateTime($year.'-12-31');

        // Si période personnalisée
        if ($request->query->has('start_date') && $request->query->has('end_date')) {
            $startDate = new DateTime($request->query->get('start_date'));
            $endDate   = new DateTime($request->query->get('end_date'));
        }

        // Récupérer toutes les métriques RH
        $metrics = $this->hrMetricsService->getAllMetrics($startDate, $endDate);

        // Analyse des gaps de compétences
        $skillGaps        = $this->skillGapAnalyzer->analyzeGlobalGaps();
        $trainingNeeds    = $this->skillGapAnalyzer->identifyTrainingNeeds();
        $recruitmentNeeds = $this->skillGapAnalyzer->getRecruitmentRecommendations();

        return $this->render('hr/dashboard.html.twig', [
            'metrics'          => $metrics,
            'skillGaps'        => $skillGaps,
            'trainingNeeds'    => $trainingNeeds,
            'recruitmentNeeds' => $recruitmentNeeds,
            'period'           => [
                'year'      => $year,
                'startDate' => $startDate,
                'endDate'   => $endDate,
            ],
        ]);
    }

    #[Route('/skills-matrix', name: 'hr_skills_matrix', methods: ['GET'])]
    public function skillsMatrix(): Response
    {
        // Matrice de compétences : tous les contributeurs actifs avec leurs compétences
        $contributorRepository = $this->hrMetricsService->contributorRepository;
        $contributors          = $contributorRepository->findBy(['active' => true], ['lastName' => 'ASC']);

        return $this->render('hr/skills_matrix.html.twig', [
            'contributors' => $contributors,
        ]);
    }

    #[Route('/gap-analysis', name: 'hr_gap_analysis', methods: ['GET'])]
    public function gapAnalysis(): Response
    {
        $skillGaps        = $this->skillGapAnalyzer->analyzeGlobalGaps();
        $trainingNeeds    = $this->skillGapAnalyzer->identifyTrainingNeeds();
        $recruitmentNeeds = $this->skillGapAnalyzer->getRecruitmentRecommendations();

        return $this->render('hr/gap_analysis.html.twig', [
            'skillGaps'        => $skillGaps,
            'trainingNeeds'    => $trainingNeeds,
            'recruitmentNeeds' => $recruitmentNeeds,
        ]);
    }
}

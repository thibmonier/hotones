<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Service\ProjectRiskAnalyzer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/risks')]
#[IsGranted('ROLE_MANAGER')]
class RiskController extends AbstractController
{
    public function __construct(
        private readonly ProjectRiskAnalyzer $riskAnalyzer,
    ) {
    }

    #[Route('/projects', name: 'risk_projects_dashboard', methods: ['GET'])]
    public function projectsDashboard(EntityManagerInterface $em): Response
    {
        // Récupérer tous les projets actifs ou en cours
        $projects = $em->getRepository(Project::class)->findBy(['status' => ['in_progress', 'active']], [
            'name' => 'ASC',
        ]);

        // Analyser les projets et récupérer ceux à risque
        $atRiskProjects = $this->riskAnalyzer->analyzeMultipleProjects($projects);

        // Statistiques globales
        $stats = [
            'total'    => count($projects),
            'atRisk'   => count($atRiskProjects),
            'critical' => count(array_filter(
                $atRiskProjects,
                fn ($p): bool => $p['analysis']['riskLevel'] === 'critical',
            )),
            'high'   => count(array_filter($atRiskProjects, fn ($p): bool => $p['analysis']['riskLevel'] === 'high')),
            'medium' => count(array_filter($atRiskProjects, fn ($p): bool => $p['analysis']['riskLevel'] === 'medium')),
        ];

        return $this->render('risk/projects_dashboard.html.twig', [
            'atRiskProjects' => $atRiskProjects,
            'stats'          => $stats,
        ]);
    }
}

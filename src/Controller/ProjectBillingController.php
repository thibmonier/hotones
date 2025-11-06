<?php

namespace App\Controller;

use App\Entity\Project;
use App\Service\BillingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projects')]
class ProjectBillingController extends AbstractController
{
    #[Route('/{id}/billing', name: 'project_billing', methods: ['GET'])]
    #[IsGranted('ROLE_INTERVENANT')]
    public function billing(Project $project, BillingService $billingService): Response
    {
        $entries = $billingService->buildProjectBillingRecap($project);

        // Regrouper par type pour l'affichage
        $totals = [
            'forfait' => 0.0,
            'regie'   => 0.0,
        ];
        foreach ($entries as $e) {
            $totals[$e['type']] += $e['amount'];
        }

        return $this->render('project/billing.html.twig', [
            'project' => $project,
            'entries' => $entries,
            'totals'  => $totals,
        ]);
    }
}

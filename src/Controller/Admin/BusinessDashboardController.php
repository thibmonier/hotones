<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Analytics\BusinessKpiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * US-093 (sprint-017 EPIC-002) — Dashboard 7 KPIs business pilotage PO.
 *
 * Cache 5 min via `BusinessKpiService::computeAll()`. Refresh auto Stimulus
 * sur la vue Twig.
 */
#[Route('/admin/business-dashboard')]
#[IsGranted('ROLE_ADMIN')]
final class BusinessDashboardController extends AbstractController
{
    public function __construct(
        private readonly BusinessKpiService $kpiService,
    ) {
    }

    #[Route('', name: 'admin_business_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $kpis = $this->kpiService->computeAll();

        return $this->render('admin/business_dashboard.html.twig', [
            'kpis' => $kpis,
        ]);
    }
}

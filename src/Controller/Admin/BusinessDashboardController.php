<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Application\Project\Query\BillingLeadTimeKpi\ComputeBillingLeadTimeKpiHandler;
use App\Service\Analytics\BusinessKpiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * US-093 (sprint-017 EPIC-002) — Dashboard 7 KPIs business pilotage PO.
 * US-111 (sprint-024 EPIC-003 Phase 4) — KPI billing lead time ajouté (T-111-04).
 *
 * Cache : BusinessKpiService 5 min (cache.analytics) + billing lead time 1 h (cache.kpi).
 */
#[Route('/admin/business-dashboard')]
#[IsGranted('ROLE_ADMIN')]
final class BusinessDashboardController extends AbstractController
{
    public function __construct(
        private readonly BusinessKpiService $kpiService,
        private readonly ComputeBillingLeadTimeKpiHandler $computeBillingLeadTimeKpi,
    ) {
    }

    #[Route('', name: 'admin_business_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/business_dashboard.html.twig', [
            'kpis' => $this->kpiService->computeAll(),
            'billing_lead_time' => ($this->computeBillingLeadTimeKpi)(),
        ]);
    }
}

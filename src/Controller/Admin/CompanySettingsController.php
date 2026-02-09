<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\CompanySettingsRepository;
use App\Service\CjmCalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/company-settings')]
#[IsGranted('ROLE_ADMIN')]
class CompanySettingsController extends AbstractController
{
    #[Route('', name: 'admin_company_settings', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CompanySettingsRepository $companySettingsRepository,
        CjmCalculatorService $cjmCalculatorService,
        EntityManagerInterface $em,
    ): Response {
        $settings = $companySettingsRepository->getSettings();

        if ($request->isMethod('POST')) {
            // Structure cost coefficient
            $structureCost = $request->request->get('structure_cost_coefficient');
            if ($structureCost !== null && $structureCost !== '') {
                $settings->setStructureCostCoefficient((string) $structureCost);
            }

            // Employer charges coefficient
            $employerCharges = $request->request->get('employer_charges_coefficient');
            if ($employerCharges !== null && $employerCharges !== '') {
                $settings->setEmployerChargesCoefficient((string) $employerCharges);
            }

            // Paid leave days
            $paidLeaveDays = $request->request->get('annual_paid_leave_days');
            if ($paidLeaveDays !== null && $paidLeaveDays !== '') {
                $settings->setAnnualPaidLeaveDays((int) $paidLeaveDays);
            }

            // RTT days
            $rttDays = $request->request->get('annual_rtt_days');
            if ($rttDays !== null && $rttDays !== '') {
                $settings->setAnnualRttDays((int) $rttDays);
            }

            $em->flush();

            $this->addFlash('success', 'Paramètres enregistrés avec succès');

            return $this->redirectToRoute('admin_company_settings');
        }

        // Obtenir le rapport de calcul pour l'année en cours
        $currentYear       = (int) date('Y');
        $calculationReport = $cjmCalculatorService->getCalculationReport($currentYear);

        // Exemple de calcul CJM avec un salaire de 3500€
        $exampleSalary = '3500';
        $exampleCjm    = $cjmCalculatorService->calculateCjmFromMonthlySalary($exampleSalary, $currentYear);

        return $this->render('admin/company_settings/index.html.twig', [
            'settings'          => $settings,
            'calculationReport' => $calculationReport,
            'exampleSalary'     => $exampleSalary,
            'exampleCjm'        => $exampleCjm,
        ]);
    }
}

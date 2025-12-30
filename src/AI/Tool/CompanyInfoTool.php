<?php

declare(strict_types=1);

namespace App\AI\Tool;

use App\Repository\CompanySettingsRepository;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

/**
 * Tool pour récupérer les informations de l'entreprise.
 *
 * Fournit aux agents IA les paramètres et coefficients de l'entreprise
 * pour des calculs de coûts précis dans les devis.
 */
#[AsTool('get_company_info', "Récupère les informations et coefficients de l'entreprise")]
final readonly class CompanyInfoTool
{
    public function __construct(
        private CompanySettingsRepository $settingsRepository,
    ) {
    }

    /**
     * @return array{
     *     structure_cost_coefficient: float,
     *     employer_charges_coefficient: float,
     *     global_charge_coefficient: float,
     *     annual_paid_leave_days: int,
     *     annual_rtt_days: int,
     *     total_leave_days: int
     * }
     */
    public function __invoke(): array
    {
        $settings = $this->settingsRepository->getSettings();

        return [
            'structure_cost_coefficient'   => (float) $settings->getStructureCostCoefficient(),
            'employer_charges_coefficient' => (float) $settings->getEmployerChargesCoefficient(),
            'global_charge_coefficient'    => (float) $settings->getGlobalChargeCoefficient(),
            'annual_paid_leave_days'       => $settings->getAnnualPaidLeaveDays(),
            'annual_rtt_days'              => $settings->getAnnualRttDays(),
            'total_leave_days'             => $settings->getAnnualPaidLeaveDays() + $settings->getAnnualRttDays(),
        ];
    }
}

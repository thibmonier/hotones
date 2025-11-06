<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderPaymentSchedule;
use App\Entity\Project;
use App\Repository\TimesheetRepository;
use DateTime;

class BillingService
{
    public function __construct(private readonly TimesheetRepository $timesheetRepository)
    {
    }

    /**
     * Construit le récapitulatif de facturation d'un projet en combinant:
     * - Forfait: échéancier des devis (dates/montants)
     * - Régie: facturation mensuelle calculée à partir des temps saisis (TJM contributeur)
     *
     * Retourne un tableau normalisé:
     *   [
     *     [ 'date' => DateTimeInterface, 'label' => string, 'amount' => float, 'type' => 'forfait'|'regie', 'order' => Order],
     *     ...
     *   ]
     */
    public function buildProjectBillingRecap(Project $project): array
    {
        $entries = [];

        foreach ($project->getOrders() as $order) {
            if ($order->getContractType() === 'forfait') {
                $entries = array_merge($entries, $this->buildForfaitEntries($order));
            } else { // regie
                $entries = array_merge($entries, $this->buildRegieEntries($order));
            }
        }

        // Tri par date croissante
        usort($entries, fn ($a, $b) => $a['date'] <=> $b['date']);

        return $entries;
    }

    /**
     * Échéancier pour contrat au forfait.
     */
    private function buildForfaitEntries(Order $order): array
    {
        $total  = $order->calculateTotalFromSections();
        $result = [];
        /** @var OrderPaymentSchedule $s */
        foreach ($order->getPaymentSchedules() as $s) {
            $result[] = [
                'date'   => $s->getBillingDate(),
                'label'  => $s->getLabel() ?: 'Échéance',
                'amount' => (float) $s->computeAmount($total),
                'type'   => 'forfait',
                'order'  => $order,
            ];
        }

        return $result;
    }

    /**
     * Facturation mensuelle pour contrat en régie (temps passé × TJM/8).
     */
    private function buildRegieEntries(Order $order): array
    {
        $project = $order->getProject();
        $rows    = $this->timesheetRepository->getMonthlyRevenueForProjectUsingContributorTjm($project);

        $result = [];
        foreach ($rows as $r) {
            $year     = (int) $r['year'];
            $month    = (int) $r['month'];
            $date     = new DateTime(sprintf('%04d-%02d-01', $year, $month));
            $result[] = [
                'date'   => $date,
                'label'  => sprintf('Régie %02d/%04d', $month, $year),
                'amount' => (float) ($r['revenue'] ?? 0),
                'type'   => 'regie',
                'order'  => $order,
            ];
        }

        return $result;
    }
}

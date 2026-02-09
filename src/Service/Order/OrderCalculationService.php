<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Entity\Order;

class OrderCalculationService
{
    /**
     * Calculates all order totals for the show view.
     *
     * @return array{sectionsTotal: float, servicesSubtotal: float, purchasesSubtotal: float, contingencyAmount: float, finalAmount: float, scheduledTotal: float}
     */
    public function calculateOrderTotals(Order $order): array
    {
        $sectionsTotal = (float) $order->calculateTotalFromSections();
        if ($sectionsTotal <= 0 && $order->getTotalAmount()) {
            $sectionsTotal = (float) $order->getTotalAmount();
        }

        $servicesSubtotal  = 0.0;
        $purchasesSubtotal = 0.0;
        foreach ($order->getSections() as $section) {
            foreach ($section->getLines() as $line) {
                $servicesSubtotal += (float) $line->getServiceAmount();

                if ($line->getPurchaseAmount()) {
                    $purchasesSubtotal += (float) $line->getPurchaseAmount();
                }

                if (in_array($line->getType(), ['purchase', 'fixed_amount'], true) && $line->getDirectAmount()) {
                    $purchasesSubtotal += (float) $line->getDirectAmount();
                }
            }
        }

        $contPct           = $order->getContingencyPercentage() ? (float) $order->getContingencyPercentage() : 0.0;
        $contingencyAmount = $sectionsTotal * ($contPct / 100.0);
        $finalTotal        = $sectionsTotal - $contingencyAmount;

        $scheduledTotal = 0.0;
        if ($order->getContractType() === 'forfait') {
            foreach ($order->getPaymentSchedules() as $s) {
                $scheduledTotal += (float) $s->computeAmount(number_format($finalTotal, 2, '.', ''));
            }
        }

        return [
            'sectionsTotal'     => $sectionsTotal,
            'servicesSubtotal'  => $servicesSubtotal,
            'purchasesSubtotal' => $purchasesSubtotal,
            'contingencyAmount' => $contingencyAmount,
            'finalAmount'       => $finalTotal,
            'scheduledTotal'    => $scheduledTotal,
        ];
    }

    /**
     * Recalculates and updates the order's total amount from sections.
     */
    public function updateOrderTotals(Order $order): void
    {
        $totalAmount = '0';

        foreach ($order->getSections() as $section) {
            $totalAmount = bcadd($totalAmount, (string) $section->getTotalAmount(), 2);
        }

        $order->setTotalAmount($totalAmount);
    }
}

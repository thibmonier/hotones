<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use DateTime;
use DateTimeInterface;

/**
 * Service de calcul des indicateurs de trésorerie.
 */
class TreasuryService
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
    ) {
    }

    /**
     * Récupère les KPIs principaux de trésorerie.
     *
     * @return array{
     *     total_billed: string,
     *     total_paid: string,
     *     pending_payment: string,
     *     overdue_amount: string,
     *     overdue_count: int,
     *     average_payment_delay: float,
     *     collection_rate: float
     * }
     */
    public function getMainKpis(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): array
    {
        // CA facturé (factures envoyées + payées + en retard)
        $totalBilled = $this->invoiceRepository->calculateTotalRevenue($startDate, $endDate);

        // CA encaissé (factures payées uniquement)
        $totalPaid = $this->invoiceRepository->calculatePaidRevenue($startDate, $endDate);

        // En attente de paiement
        $pendingPayment = bcsub($totalBilled, $totalPaid, 2);

        // Factures en retard
        $overdueInvoices = $this->invoiceRepository->findOverdueInvoices();
        $overdueAmount   = '0.00';
        foreach ($overdueInvoices as $invoice) {
            $overdueAmount = bcadd($overdueAmount, $invoice->getAmountTtc(), 2);
        }

        // Délai moyen de paiement
        $averagePaymentDelay = $this->invoiceRepository->calculateAveragePaymentDelay();

        // Taux de recouvrement
        $collectionRate = bccomp($totalBilled, '0.00', 2) > 0
            ? (float) bcmul(bcdiv($totalPaid, $totalBilled, 4), '100', 2)
            : 0.0;

        return [
            'total_billed'          => $totalBilled,
            'total_paid'            => $totalPaid,
            'pending_payment'       => $pendingPayment,
            'overdue_amount'        => $overdueAmount,
            'overdue_count'         => count($overdueInvoices),
            'average_payment_delay' => $averagePaymentDelay,
            'collection_rate'       => $collectionRate,
        ];
    }

    /**
     * Calcule le prévisionnel de trésorerie sur N jours.
     * Retourne les encaissements prévus groupés par semaine.
     *
     * @return array<string, array{week_start: string, expected_amount: string, invoice_count: int}>
     */
    public function getForecast(int $days = 90): array
    {
        $today   = new DateTime();
        $endDate = (clone $today)->modify("+{$days} days");

        // Récupérer toutes les factures non payées avec échéance dans la période
        $qb = $this->invoiceRepository->createQueryBuilder('i');
        $qb
            ->where('i.status IN (:statuses)')
            ->andWhere('i.dueDate BETWEEN :start AND :end')
            ->setParameter('statuses', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])
            ->setParameter('start', $today)
            ->setParameter('end', $endDate)
            ->orderBy('i.dueDate', 'ASC');

        $invoices = $qb->getQuery()->getResult();

        // Grouper par semaine
        $forecast = [];
        foreach ($invoices as $invoice) {
            $dueDate   = $invoice->getDueDate();
            $weekStart = (clone $dueDate)->modify('monday this week');
            $weekKey   = $weekStart->format('Y-m-d');

            if (!isset($forecast[$weekKey])) {
                $forecast[$weekKey] = [
                    'week_start'      => $weekStart->format('d/m/Y'),
                    'expected_amount' => '0.00',
                    'invoice_count'   => 0,
                ];
            }

            $forecast[$weekKey]['expected_amount'] = bcadd(
                $forecast[$weekKey]['expected_amount'],
                (string) $invoice->getAmountTtc(),
                2,
            );
            ++$forecast[$weekKey]['invoice_count'];
        }

        return $forecast;
    }

    /**
     * Récupère les factures en retard avec détails.
     *
     * @return array<int, array{
     *     invoice: Invoice,
     *     days_late: int,
     *     priority: string
     * }>
     */
    public function getOverdueInvoices(): array
    {
        $overdueInvoices = $this->invoiceRepository->findOverdueInvoices();
        $today           = new DateTime();

        $result = [];
        foreach ($overdueInvoices as $invoice) {
            $dueDate  = $invoice->getDueDate();
            $daysLate = $today->diff($dueDate)->days;

            // Priorité selon le retard
            $priority = match (true) {
                $daysLate >= 60 => 'critical',
                $daysLate >= 30 => 'high',
                $daysLate >= 15 => 'medium',
                default         => 'low',
            };

            $result[] = [
                'invoice'   => $invoice,
                'days_late' => $daysLate,
                'priority'  => $priority,
            ];
        }

        // Trier par priorité puis par retard
        usort($result, function ($a, $b) {
            $priorityOrder   = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            $priorityCompare = $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }

            return $b['days_late'] <=> $a['days_late'];
        });

        return $result;
    }

    /**
     * Calcule les KPIs par client (top clients).
     *
     * @return array<int, array{
     *     client_id: int,
     *     client_name: string,
     *     total_billed: string,
     *     total_paid: string,
     *     pending_amount: string,
     *     invoice_count: int,
     *     average_payment_delay: float
     * }>
     */
    public function getClientStats(int $limit = 10): array
    {
        $qb = $this->invoiceRepository->createQueryBuilder('i');
        $qb
            ->select(
                'c.id as client_id',
                'c.name as client_name',
                'COUNT(i.id) as invoice_count',
                'SUM(i.amountHt) as total_billed',
            )
            ->join('i.client', 'c')
            ->where('i.status IN (:statuses)')
            ->setParameter('statuses', [Invoice::STATUS_SENT, Invoice::STATUS_PAID, Invoice::STATUS_OVERDUE])
            ->groupBy('c.id')
            ->orderBy('total_billed', 'DESC')
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();

        $clientStats = [];
        foreach ($results as $row) {
            $clientId = (int) $row['client_id'];

            // Calculer le montant payé pour ce client
            $qbPaid = $this->invoiceRepository->createQueryBuilder('i2');
            $qbPaid
                ->select('SUM(i2.amountHt) as total_paid')
                ->where('i2.client = :clientId')
                ->andWhere('i2.status = :statusPaid')
                ->setParameter('clientId', $clientId)
                ->setParameter('statusPaid', Invoice::STATUS_PAID);

            $paidResult = $qbPaid->getQuery()->getSingleScalarResult();
            $totalPaid  = $paidResult ? (string) $paidResult : '0.00';

            // Calculer le délai moyen de paiement pour ce client
            $qbDelay = $this->invoiceRepository->createQueryBuilder('i3');
            $qbDelay
                ->select('AVG(DATEDIFF(i3.paidAt, i3.issuedAt)) as avg_delay')
                ->where('i3.client = :clientId')
                ->andWhere('i3.status = :statusPaid')
                ->setParameter('clientId', $clientId)
                ->setParameter('statusPaid', Invoice::STATUS_PAID);

            $delayResult = $qbDelay->getQuery()->getSingleScalarResult();
            $avgDelay    = $delayResult ? (float) $delayResult : 0.0;

            $totalBilled   = (string) $row['total_billed'];
            $pendingAmount = bcsub($totalBilled, $totalPaid, 2);

            $clientStats[] = [
                'client_id'             => $clientId,
                'client_name'           => (string) $row['client_name'],
                'total_billed'          => $totalBilled,
                'total_paid'            => $totalPaid,
                'pending_amount'        => $pendingAmount,
                'invoice_count'         => (int) $row['invoice_count'],
                'average_payment_delay' => $avgDelay,
            ];
        }

        return $clientStats;
    }

    /**
     * Calcule l'évolution mensuelle de la trésorerie.
     *
     * @return array<string, array{month: string, billed: string, paid: string}>
     */
    public function getMonthlyTrend(int $months = 12): array
    {
        $result = [];
        $today  = new DateTime();

        for ($i = $months - 1; $i >= 0; --$i) {
            $date      = (clone $today)->modify("-{$i} months");
            $startDate = (clone $date)->modify('first day of this month');
            $endDate   = (clone $date)->modify('last day of this month');

            $billed = $this->invoiceRepository->calculateTotalRevenue($startDate, $endDate);
            $paid   = $this->invoiceRepository->calculatePaidRevenue($startDate, $endDate);

            $monthKey          = $date->format('Y-m');
            $result[$monthKey] = [
                'month'  => $date->format('M Y'),
                'billed' => $billed,
                'paid'   => $paid,
            ];
        }

        return $result;
    }
}

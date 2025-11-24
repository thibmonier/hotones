<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;
use DateTime;
use DateTimeImmutable;

class WorkloadPredictionService
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {
    }

    /**
     * Analyse le pipeline de devis et prédit la charge future.
     *
     * @return array{
     *     pipeline: array,
     *     workloadByMonth: array,
     *     alerts: array,
     *     totalPotentialDays: float
     * }
     */
    public function analyzePipeline(): array
    {
        // Récupérer tous les devis en attente de signature
        $pendingOrders = $this->orderRepository->findBy(
            ['status' => 'a_signer'],
            ['createdAt' => 'DESC'],
        );

        $pipeline           = [];
        $workloadByMonth    = [];
        $alerts             = [];
        $totalPotentialDays = 0;

        foreach ($pendingOrders as $order) {
            $analysis   = $this->analyzeOrder($order);
            $pipeline[] = $analysis;

            // Accumuler la charge par mois si probabilité > 30%
            if ($analysis['winProbability'] > 30) {
                $this->addWorkloadToMonth($workloadByMonth, $order, $analysis['winProbability']);
                $totalPotentialDays += $analysis['totalDays'] * ($analysis['winProbability'] / 100);
            }
        }

        // Trier le pipeline par probabilité décroissante
        usort($pipeline, fn ($a, $b) => $b['winProbability'] <=> $a['winProbability']);

        // Détecter les alertes de surcharge/sous-charge
        $alerts = $this->detectWorkloadAlerts($workloadByMonth);

        return [
            'pipeline'           => $pipeline,
            'workloadByMonth'    => $workloadByMonth,
            'alerts'             => $alerts,
            'totalPotentialDays' => round($totalPotentialDays, 1),
        ];
    }

    /**
     * Analyse un devis pour calculer sa probabilité de gain.
     */
    private function analyzeOrder(Order $order): array
    {
        $project     = $order->getProject();
        $client      = $project?->getClient();
        $salesPerson = $project?->getSalesPerson();

        // Calcul de la probabilité de gain basée sur plusieurs facteurs
        $probability = 50; // Base 50%

        // Facteur 1 : Historique client (taux de conversion)
        if ($client) {
            $clientConversionRate = $this->getClientConversionRate($client->getId());
            $probability += ($clientConversionRate - 50) * 0.3; // Poids 30%
        }

        // Facteur 2 : Historique commercial (taux de conversion)
        if ($salesPerson) {
            $salesPersonRate = $this->getSalesPersonConversionRate($salesPerson->getId());
            $probability += ($salesPersonRate - 50) * 0.2; // Poids 20%
        }

        // Facteur 3 : Âge du devis (pénalité si ancien)
        $daysOld = (new DateTime())->diff($order->getCreatedAt())->days;
        if ($daysOld > 60) {
            $probability -= 20; // Très ancien
        } elseif ($daysOld > 30) {
            $probability -= 10; // Ancien
        } elseif ($daysOld < 7) {
            $probability += 5; // Très récent
        }

        // Facteur 4 : Montant du devis (les gros montants ont moins de chance)
        $amount = (float) $order->getTotalAmount();
        if ($amount > 100000) {
            $probability -= 15;
        } elseif ($amount > 50000) {
            $probability -= 5;
        } elseif ($amount < 10000) {
            $probability += 10; // Petits montants plus faciles à signer
        }

        // Borner entre 0 et 100
        $probability = max(0, min(100, $probability));

        // Calculer la charge (jours)
        $totalDays = $this->calculateOrderDays($order);

        return [
            'order'          => $order,
            'winProbability' => round($probability, 1),
            'totalDays'      => $totalDays,
            'amount'         => $amount,
            'daysOld'        => $daysOld,
            'client'         => $client?->getName()  ?? 'N/A',
            'project'        => $project?->getName() ?? 'N/A',
        ];
    }

    /**
     * Calcule le taux de conversion d'un client.
     */
    private function getClientConversionRate(int $clientId): float
    {
        $allOrders = $this->orderRepository->findBy(['project' => ['client' => $clientId]]);
        if (count($allOrders) === 0) {
            return 50; // Pas d'historique, on garde 50%
        }

        $signedCount = 0;
        foreach ($allOrders as $order) {
            if (in_array($order->getStatus(), ['signe', 'gagne', 'termine'], true)) {
                ++$signedCount;
            }
        }

        return (count($allOrders) > 0) ? ($signedCount / count($allOrders)) * 100 : 50;
    }

    /**
     * Calcule le taux de conversion d'un commercial.
     */
    private function getSalesPersonConversionRate(int $salesPersonId): float
    {
        // Récupérer tous les projets du commercial
        $orders = $this->orderRepository->createQueryBuilder('o')
            ->join('o.project', 'p')
            ->where('p.salesPerson = :salesPersonId')
            ->setParameter('salesPersonId', $salesPersonId)
            ->getQuery()
            ->getResult();

        if (count($orders) === 0) {
            return 50;
        }

        $signedCount = 0;
        foreach ($orders as $order) {
            if (in_array($order->getStatus(), ['signe', 'gagne', 'termine'], true)) {
                ++$signedCount;
            }
        }

        return (count($orders) > 0) ? ($signedCount / count($orders)) * 100 : 50;
    }

    /**
     * Calcule le nombre de jours d'un devis.
     */
    private function calculateOrderDays(Order $order): float
    {
        $totalDays = 0;

        foreach ($order->getSections() as $section) {
            $totalDays += (float) $section->getTotalDays();
        }

        return $totalDays;
    }

    /**
     * Ajoute la charge d'un devis à la répartition mensuelle.
     */
    private function addWorkloadToMonth(array &$workloadByMonth, Order $order, float $probability): void
    {
        $project = $order->getProject();
        if (!$project || !$project->getStartDate()) {
            return;
        }

        $startDate = $project->getStartDate();
        $duration  = $project->getEndDate()
            ? $startDate->diff($project->getEndDate())->days
            : 90; // Par défaut 3 mois

        $totalDays    = $this->calculateOrderDays($order);
        $daysPerMonth = $duration > 0 ? $totalDays / (max(1, $duration / 30)) : $totalDays;

        // Répartir sur 3 mois max
        $monthsToSpread = min(3, ceil($duration / 30));
        for ($i = 0; $i < $monthsToSpread; ++$i) {
            $month = (clone $startDate)->modify("+{$i} months")->format('Y-m');

            if (!isset($workloadByMonth[$month])) {
                $workloadByMonth[$month] = [
                    'potential' => 0,
                    'confirmed' => 0,
                    'orders'    => [],
                ];
            }

            $workloadByMonth[$month]['potential'] += ($daysPerMonth / $monthsToSpread) * ($probability / 100);
            $workloadByMonth[$month]['orders'][] = [
                'orderNumber' => $order->getOrderNumber(),
                'days'        => $daysPerMonth / $monthsToSpread,
                'probability' => $probability,
            ];
        }
    }

    /**
     * Détecte les alertes de surcharge ou sous-charge.
     */
    private function detectWorkloadAlerts(array $workloadByMonth): array
    {
        $alerts = [];

        // Capacité de l'équipe (à paramétrer selon vos besoins)
        // TODO: récupérer dynamiquement depuis la config ou les contributeurs actifs
        $teamCapacityPerMonth = 20; // 20 jours/mois d'équipe

        foreach ($workloadByMonth as $month => $data) {
            $totalLoad    = $data['potential'] + $data['confirmed'];
            $capacityRate = ($totalLoad / $teamCapacityPerMonth) * 100;

            if ($capacityRate > 120) {
                $alerts[] = [
                    'month'    => $month,
                    'type'     => 'overload',
                    'severity' => 'critical',
                    'message'  => sprintf(
                        'Surcharge critique en %s : %.0f%% de la capacité (%.1f jours sur %d disponibles)',
                        (new DateTimeImmutable($month))->format('F Y'),
                        $capacityRate,
                        $totalLoad,
                        $teamCapacityPerMonth,
                    ),
                    'capacityRate' => round($capacityRate, 1),
                ];
            } elseif ($capacityRate > 100) {
                $alerts[] = [
                    'month'    => $month,
                    'type'     => 'overload',
                    'severity' => 'high',
                    'message'  => sprintf(
                        'Surcharge en %s : %.0f%% de la capacité',
                        (new DateTimeImmutable($month))->format('F Y'),
                        $capacityRate,
                    ),
                    'capacityRate' => round($capacityRate, 1),
                ];
            } elseif ($capacityRate < 50) {
                $alerts[] = [
                    'month'    => $month,
                    'type'     => 'underload',
                    'severity' => 'medium',
                    'message'  => sprintf(
                        'Sous-charge en %s : seulement %.0f%% de la capacité utilisée',
                        (new DateTimeImmutable($month))->format('F Y'),
                        $capacityRate,
                    ),
                    'capacityRate' => round($capacityRate, 1),
                ];
            }
        }

        return $alerts;
    }
}

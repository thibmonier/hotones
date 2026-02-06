<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\Planning;
use App\Repository\ContributorRepository;
use App\Repository\OrderRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

class WorkloadPredictionService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly ContributorRepository $contributorRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Analyse le pipeline de devis et prédit la charge future.
     *
     * @param array $profileIds       IDs des profils à filtrer (vide = tous)
     * @param array $contributorIds   IDs des contributeurs à filtrer (vide = tous)
     * @param bool  $includeConfirmed Inclure la charge confirmée (devis signés/gagnés)
     *
     * @return array{
     *     pipeline: array,
     *     workloadByMonth: array,
     *     alerts: array,
     *     totalPotentialDays: float
     * }
     */
    public function analyzePipeline(array $profileIds = [], array $contributorIds = [], bool $includeConfirmed = false): array
    {
        $pipeline           = [];
        $workloadByMonth    = [];
        $alerts             = [];
        $totalPotentialDays = 0;

        // Toujours inclure la charge confirmée depuis les plannings
        $this->addConfirmedWorkloadFromPlannings($workloadByMonth, $profileIds, $contributorIds);

        // Ajouter la charge confirmée depuis les devis si demandé
        if ($includeConfirmed) {
            $confirmedOrders = $this->orderRepository->findBy(
                ['status' => ['signe', 'gagne', 'en_cours']],
            );

            foreach ($confirmedOrders as $order) {
                $this->addConfirmedWorkloadToMonth($workloadByMonth, $order, $profileIds, $contributorIds);
            }
        }

        // Récupérer tous les devis en attente de signature
        $pendingOrders = $this->orderRepository->findBy(
            ['status' => 'a_signer'],
        );

        foreach ($pendingOrders as $order) {
            $analysis = $this->analyzeOrder($order, $profileIds, $contributorIds);

            // Ne pas inclure les devis qui n'ont aucun jour correspondant aux filtres
            if ($analysis['totalDays'] > 0) {
                $pipeline[] = $analysis;

                // Accumuler la charge par mois si probabilité > 30%
                if ($analysis['winProbability'] > 30) {
                    $this->addWorkloadToMonth($workloadByMonth, $order, $analysis['winProbability'], $profileIds, $contributorIds);
                    $totalPotentialDays += $analysis['totalDays'] * ($analysis['winProbability'] / 100);
                }
            }
        }

        // Trier le pipeline par probabilité décroissante
        usort($pipeline, fn ($a, $b): int => $b['winProbability'] <=> $a['winProbability']);

        // Trier les mois chronologiquement
        ksort($workloadByMonth);

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
    private function analyzeOrder(Order $order, array $profileIds = [], array $contributorIds = []): array
    {
        $project     = $order->project;
        $client      = $project?->client;
        $salesPerson = $project?->salesPerson;

        // Calcul de la probabilité de gain basée sur plusieurs facteurs
        $probability = 50; // Base 50%

        // Facteur 1 : Historique client (taux de conversion)
        if ($client) {
            $clientConversionRate = $this->getClientConversionRate($client->id);
            $probability += ($clientConversionRate - 50) * 0.3; // Poids 30%
        }

        // Facteur 2 : Historique commercial (taux de conversion)
        if ($salesPerson) {
            $salesPersonRate = $this->getSalesPersonConversionRate($salesPerson->getId());
            $probability += ($salesPersonRate - 50) * 0.2; // Poids 20%
        }

        // Facteur 3 : Âge du devis (pénalité si ancien)
        $createdAt = $order->getCreatedAt();
        $daysOld   = 0;
        if ($createdAt !== null) {
            $daysOld = new DateTime()->diff($createdAt)->days;
            if ($daysOld > 60) {
                $probability -= 20; // Très ancien
            } elseif ($daysOld > 30) {
                $probability -= 10; // Ancien
            } elseif ($daysOld < 7) {
                $probability += 5; // Très récent
            }
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

        // Calculer la charge (jours) avec filtres
        $totalDays = $this->calculateOrderDays($order, $profileIds, $contributorIds);

        return [
            'order'          => $order,
            'winProbability' => round($probability, 1),
            'totalDays'      => $totalDays,
            'amount'         => $amount,
            'daysOld'        => $daysOld,
            'client'         => $client?->name  ?? 'N/A',
            'project'        => $project?->name ?? 'N/A',
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

        return ($signedCount / count($allOrders)) * 100;
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

        return ($signedCount / count($orders)) * 100;
    }

    /**
     * Calcule le nombre de jours d'un devis en fonction des filtres.
     */
    private function calculateOrderDays(Order $order, array $profileIds = [], array $contributorIds = []): float
    {
        $totalDays = 0;

        // Si des contributeurs sont spécifiés, récupérer leurs profils
        // et les fusionner avec les profils directement spécifiés
        $effectiveProfileIds = $profileIds;
        if (!empty($contributorIds)) {
            $contributorProfileIds = $this->getProfileIdsFromContributors($contributorIds);
            if (!empty($contributorProfileIds)) {
                $effectiveProfileIds = empty($effectiveProfileIds)
                    ? $contributorProfileIds
                    : array_intersect($effectiveProfileIds, $contributorProfileIds);
            }
        }

        foreach ($order->getSections() as $section) {
            foreach ($section->getLines() as $line) {
                // Ne compter que les lignes de type "service"
                if ($line->type !== 'service') {
                    continue;
                }
                if (!$line->days) {
                    continue;
                }
                // Filtrer par profil (incluant les profils des contributeurs sélectionnés)
                if (!empty($effectiveProfileIds) && $line->getProfile()) {
                    if (!in_array($line->getProfile()->getId(), $effectiveProfileIds, true)) {
                        continue;
                    }
                }

                $totalDays += (float) $line->days;
            }
        }

        return $totalDays;
    }

    /**
     * Ajoute la charge d'un devis à la répartition mensuelle.
     */
    private function addWorkloadToMonth(array &$workloadByMonth, Order $order, float $probability, array $profileIds = [], array $contributorIds = []): void
    {
        $project = $order->getProject();
        if (!$project || !$project->getStartDate()) {
            return;
        }

        $startDate = $project->getStartDate();
        $duration  = $project->getEndDate()
            ? $startDate->diff($project->getEndDate())->days
            : 90; // Par défaut 3 mois

        $totalDays    = $this->calculateOrderDays($order, $profileIds, $contributorIds);
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
     * Ajoute la charge confirmée depuis les entités Planning.
     */
    private function addConfirmedWorkloadFromPlannings(array &$workloadByMonth, array $profileIds = [], array $contributorIds = []): void
    {
        $qb = $this->entityManager->getRepository(Planning::class)
            ->createQueryBuilder('p')
            ->where('p.status IN (:statuses)')
            ->andWhere('p.endDate >= :today')
            ->setParameter('statuses', ['planned', 'confirmed'])
            ->setParameter('today', new DateTime('first day of this month'));

        if (!empty($contributorIds)) {
            $qb->andWhere('p.contributor IN (:contributorIds)')
               ->setParameter('contributorIds', $contributorIds);
        }

        $plannings = $qb->getQuery()->getResult();

        foreach ($plannings as $planning) {
            /** @var Planning $planning */
            $start = max($planning->getStartDate(), new DateTime('first day of this month'));
            $end   = $planning->getEndDate();

            $dailyHours = (float) $planning->getDailyHours();
            $dailyDays  = $dailyHours / 8.0;

            // Distribuer les jours par mois
            $current   = new DateTime($start->format('Y-m-01'));
            $lastMonth = new DateTime($end->format('Y-m-01'));

            while ($current <= $lastMonth) {
                $month = $current->format('Y-m');

                // Calculer les jours ouvrés de ce planning dans ce mois
                $monthStart = max($start, new DateTime($current->format('Y-m-01')));
                $monthEnd   = min($end, new DateTime($current->format('Y-m-t')));

                $workingDays = $this->countWorkingDays($monthStart, $monthEnd);
                $plannedDays = $workingDays * $dailyDays;

                if ($plannedDays > 0) {
                    if (!isset($workloadByMonth[$month])) {
                        $workloadByMonth[$month] = [
                            'potential' => 0,
                            'confirmed' => 0,
                            'orders'    => [],
                        ];
                    }

                    $workloadByMonth[$month]['confirmed'] += $plannedDays;
                }

                $current->modify('+1 month');
            }
        }
    }

    /**
     * Compte les jours ouvrés entre deux dates.
     */
    private function countWorkingDays(DateTimeInterface $start, DateTimeInterface $end): int
    {
        $days    = 0;
        $current = clone $start;

        while ($current <= $end) {
            if (!in_array((int) $current->format('w'), [0, 6], true)) {
                ++$days;
            }
            $current->modify('+1 day');
        }

        return $days;
    }

    /**
     * Détecte les alertes de surcharge ou sous-charge.
     */
    private function detectWorkloadAlerts(array $workloadByMonth): array
    {
        $alerts = [];

        // Capacité de l'équipe = nombre de contributeurs actifs × 20 jours/mois
        $activeContributors   = $this->contributorRepository->findBy(['active' => true]);
        $teamCapacityPerMonth = max(20, count($activeContributors) * 20);

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
                        new DateTimeImmutable($month)->format('F Y'),
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
                        new DateTimeImmutable($month)->format('F Y'),
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
                        new DateTimeImmutable($month)->format('F Y'),
                        $capacityRate,
                    ),
                    'capacityRate' => round($capacityRate, 1),
                ];
            }
        }

        return $alerts;
    }

    /**
     * Récupère les IDs des profils associés aux contributeurs.
     */
    private function getProfileIdsFromContributors(array $contributorIds): array
    {
        if (empty($contributorIds)) {
            return [];
        }

        $contributors = $this->contributorRepository->findBy(['id' => $contributorIds]);
        $profileIds   = [];

        foreach ($contributors as $contributor) {
            foreach ($contributor->getProfiles() as $profile) {
                $profileIds[] = $profile->getId();
            }
        }

        return array_unique($profileIds);
    }

    /**
     * Ajoute la charge confirmée d'un devis à la répartition mensuelle.
     */
    private function addConfirmedWorkloadToMonth(array &$workloadByMonth, Order $order, array $profileIds = [], array $contributorIds = []): void
    {
        $project = $order->project;
        if (!$project || !$project->startDate) {
            return;
        }

        $startDate = $project->startDate;
        $endDate   = $project->endDate;

        if (!$endDate) {
            // Si pas de date de fin, prévoir 3 mois par défaut
            $endDate = (clone $startDate)->modify('+3 months');
        }

        $duration  = $startDate->diff($endDate)->days;
        $totalDays = $this->calculateOrderDays($order, $profileIds, $contributorIds);

        if ($totalDays <= 0) {
            return;
        }

        $daysPerMonth = $duration > 0 ? $totalDays / (max(1, $duration / 30)) : $totalDays;

        // Répartir sur la durée du projet
        $monthsToSpread = max(1, ceil($duration / 30));
        for ($i = 0; $i < $monthsToSpread; ++$i) {
            $month = (clone $startDate)->modify("+{$i} months")->format('Y-m');

            if (!isset($workloadByMonth[$month])) {
                $workloadByMonth[$month] = [
                    'potential' => 0,
                    'confirmed' => 0,
                    'orders'    => [],
                ];
            }

            $workloadByMonth[$month]['confirmed'] += $daysPerMonth / $monthsToSpread;
        }
    }
}

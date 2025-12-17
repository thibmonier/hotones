<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Project;

class ProfitabilityPredictor
{
    /**
     * Prédit la rentabilité finale d'un projet basé sur sa progression actuelle.
     *
     * @return array{
     *     canPredict: bool,
     *     currentProgress: float,
     *     currentMargin: float,
     *     predictedMargin: array,
     *     budgetDrift: array,
     *     recommendations: array,
     *     scenarios: array
     * }
     */
    public function predictProfitability(Project $project): array
    {
        $progress = (float) $project->getGlobalProgress();

        // On peut prédire si >= 30% de réalisation
        if ($progress < 30) {
            return [
                'canPredict'      => false,
                'currentProgress' => $progress,
                'message'         => 'Pas assez de données (progression < 30%)',
                'currentMargin'   => 0.0,
                'predictedMargin' => [],
                'budgetDrift'     => [],
                'recommendations' => [],
                'scenarios'       => [],
            ];
        }

        // Données actuelles
        $soldAmount = (float) $project->getTotalSoldAmount();
        $soldHours  = (float) $project->getTotalTasksSoldHours();
        $spentHours = (float) $project->getTotalTasksSpentHours();

        if ($soldAmount == 0 || $soldHours == 0) {
            return [
                'canPredict'      => false,
                'currentProgress' => $progress,
                'message'         => 'Données insuffisantes (CA ou budget manquant)',
                'currentMargin'   => 0.0,
                'predictedMargin' => [],
                'budgetDrift'     => [],
                'recommendations' => [],
                'scenarios'       => [],
            ];
        }

        // Coût actuel estimé (simplifié : 400€/jour de coût moyen)
        $costPerDay    = 400;
        $currentCost   = ($spentHours / 7)                                              * $costPerDay; // 7h par jour
        $currentMargin = $soldAmount > 0 ? (($soldAmount - $currentCost) / $soldAmount) * 100 : 0;

        // Prédiction de la marge finale
        $predictedMargin = $this->calculatePredictedMargin($project, $progress, $soldAmount, $soldHours, $spentHours, $costPerDay);

        // Analyse de dérive budgétaire
        $budgetDrift = $this->analyzeBudgetDrift($progress, $soldHours, $spentHours);

        // Génération des recommandations
        $recommendations = $this->generateRecommendations($project, $predictedMargin, $budgetDrift);

        // Scénarios
        $scenarios = $this->generateScenarios($project, $soldAmount, $soldHours, $spentHours, $progress, $costPerDay);

        return [
            'canPredict'      => true,
            'currentProgress' => $progress,
            'currentMargin'   => round($currentMargin, 2),
            'predictedMargin' => $predictedMargin,
            'budgetDrift'     => $budgetDrift,
            'recommendations' => $recommendations,
            'scenarios'       => $scenarios,
        ];
    }

    /**
     * Calcule la marge finale prédite.
     */
    private function calculatePredictedMargin(
        Project $project,
        float $progress,
        float $soldAmount,
        float $soldHours,
        float $spentHours,
        float $costPerDay
    ): array {
        // Estimation linéaire basée sur le rythme actuel
        $burnRate            = $progress > 0 ? $spentHours / $progress : 0;
        $projectedTotalHours = $burnRate * 100; // Projection à 100%

        $projectedTotalCost = ($projectedTotalHours / 7)                                            * $costPerDay;
        $projectedMargin    = $soldAmount > 0 ? (($soldAmount - $projectedTotalCost) / $soldAmount) * 100 : 0;

        // Marge budgétée initiale (objectif)
        $budgetedCost   = ($soldHours / 7)                                                * $costPerDay;
        $budgetedMargin = $soldAmount > 0 ? (($soldAmount - $budgetedCost) / $soldAmount) * 100 : 0;

        return [
            'projected'           => round($projectedMargin, 2),
            'budgeted'            => round($budgetedMargin, 2),
            'difference'          => round($projectedMargin - $budgetedMargin, 2),
            'projectedTotalHours' => round($projectedTotalHours, 1),
            'projectedTotalCost'  => round($projectedTotalCost, 0),
        ];
    }

    /**
     * Analyse la dérive budgétaire.
     */
    private function analyzeBudgetDrift(float $progress, float $soldHours, float $spentHours): array
    {
        $expectedHoursAtProgress = ($soldHours * $progress) / 100;
        $overrun                 = $spentHours - $expectedHoursAtProgress;
        $overrunPercentage       = $expectedHoursAtProgress > 0
            ? ($overrun / $expectedHoursAtProgress) * 100
            : 0;

        $severity = 'low';
        if ($overrunPercentage > 30) {
            $severity = 'critical';
        } elseif ($overrunPercentage > 15) {
            $severity = 'high';
        } elseif ($overrunPercentage > 5) {
            $severity = 'medium';
        }

        return [
            'hasOverrun'        => $overrun > 0,
            'overrunHours'      => round($overrun, 1),
            'overrunPercentage' => round($overrunPercentage, 1),
            'severity'          => $severity,
            'expectedHours'     => round($expectedHoursAtProgress, 1),
            'actualHours'       => round($spentHours, 1),
        ];
    }

    /**
     * Génère des recommandations d'action.
     */
    private function generateRecommendations(Project $project, array $predictedMargin, array $budgetDrift): array
    {
        $recommendations = [];

        // Recommandation 1 : Dérive budgétaire
        if ($budgetDrift['severity'] === 'critical') {
            $recommendations[] = [
                'priority' => 'high',
                'type'     => 'budget_control',
                'title'    => 'Action urgente requise',
                'message'  => sprintf(
                    'Dérive budgétaire critique de %.1f heures (%.0f%%). Convoquer une réunion d\'urgence pour analyser les causes.',
                    $budgetDrift['overrunHours'],
                    $budgetDrift['overrunPercentage'],
                ),
                'actions' => [
                    'Analyser les tâches en surcharge',
                    'Identifier les causes du dépassement',
                    'Réduire le scope si possible',
                    'Négocier un avenant avec le client',
                ],
            ];
        } elseif ($budgetDrift['severity'] === 'high') {
            $recommendations[] = [
                'priority' => 'medium',
                'type'     => 'budget_control',
                'title'    => 'Surveillance nécessaire',
                'message'  => sprintf(
                    'Dérive budgétaire de %.1f heures (%.0f%%). Surveiller de près.',
                    $budgetDrift['overrunHours'],
                    $budgetDrift['overrunPercentage'],
                ),
                'actions' => [
                    'Renforcer le suivi hebdomadaire',
                    'Optimiser l\'allocation des ressources',
                    'Prioriser les fonctionnalités essentielles',
                ],
            ];
        }

        // Recommandation 2 : Marge prédite faible
        if ($predictedMargin['projected'] < 10) {
            $recommendations[] = [
                'priority' => 'high',
                'type'     => 'profitability',
                'title'    => 'Marge finale prédite très faible',
                'message'  => sprintf(
                    'Marge finale estimée à %.1f%% (objectif : %.1f%%). Risque de perte.',
                    $predictedMargin['projected'],
                    $predictedMargin['budgeted'],
                ),
                'actions' => [
                    'Réduire les fonctionnalités secondaires',
                    'Affecter des profils moins coûteux si possible',
                    'Négocier un avenant pour augmenter le budget',
                    'Documenter les raisons du dépassement',
                ],
            ];
        } elseif ($predictedMargin['projected'] < 20) {
            $recommendations[] = [
                'priority' => 'medium',
                'type'     => 'profitability',
                'title'    => 'Marge finale sous l\'objectif',
                'message'  => sprintf(
                    'Marge finale estimée à %.1f%% (objectif : 20%%+).',
                    $predictedMargin['projected'],
                ),
                'actions' => [
                    'Optimiser les processus de développement',
                    'Éviter les perfectionnismes inutiles',
                    'Capitaliser sur les composants réutilisables',
                ],
            ];
        }

        // Recommandation 3 : Excellente performance
        if (count($recommendations) === 0 && $predictedMargin['projected'] > $predictedMargin['budgeted']) {
            $recommendations[] = [
                'priority' => 'low',
                'type'     => 'positive',
                'title'    => 'Performance excellente',
                'message'  => sprintf(
                    'Le projet est en avance sur les objectifs de rentabilité (%.1f%% vs %.1f%% budgeté).',
                    $predictedMargin['projected'],
                    $predictedMargin['budgeted'],
                ),
                'actions' => [
                    'Documenter les bonnes pratiques',
                    'Partager les learnings avec l\'équipe',
                    'Capitaliser sur cette dynamique',
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Génère les scénarios (optimiste, réaliste, pessimiste).
     */
    private function generateScenarios(
        Project $project,
        float $soldAmount,
        float $soldHours,
        float $spentHours,
        float $progress,
        float $costPerDay
    ): array {
        $burnRate = $progress > 0 ? $spentHours / $progress : 0;

        // Scénario réaliste : tendance actuelle
        $realisticHours  = $burnRate                                                        * 100;
        $realisticCost   = ($realisticHours / 7)                                            * $costPerDay;
        $realisticMargin = $soldAmount > 0 ? (($soldAmount - $realisticCost) / $soldAmount) * 100 : 0;

        // Scénario optimiste : amélioration de 15%
        $optimisticHours  = $realisticHours                                                   * 0.85;
        $optimisticCost   = ($optimisticHours / 7)                                            * $costPerDay;
        $optimisticMargin = $soldAmount > 0 ? (($soldAmount - $optimisticCost) / $soldAmount) * 100 : 0;

        // Scénario pessimiste : dérive de 20%
        $pessimisticHours  = $realisticHours                                                    * 1.20;
        $pessimisticCost   = ($pessimisticHours / 7)                                            * $costPerDay;
        $pessimisticMargin = $soldAmount > 0 ? (($soldAmount - $pessimisticCost) / $soldAmount) * 100 : 0;

        return [
            'optimistic' => [
                'label'      => 'Optimiste (-15%)',
                'totalHours' => round($optimisticHours, 1),
                'totalCost'  => round($optimisticCost, 0),
                'margin'     => round($optimisticMargin, 2),
                'profit'     => round($soldAmount - $optimisticCost, 0),
            ],
            'realistic' => [
                'label'      => 'Réaliste (tendance actuelle)',
                'totalHours' => round($realisticHours, 1),
                'totalCost'  => round($realisticCost, 0),
                'margin'     => round($realisticMargin, 2),
                'profit'     => round($soldAmount - $realisticCost, 0),
            ],
            'pessimistic' => [
                'label'      => 'Pessimiste (+20%)',
                'totalHours' => round($pessimisticHours, 1),
                'totalCost'  => round($pessimisticCost, 0),
                'margin'     => round($pessimisticMargin, 2),
                'profit'     => round($soldAmount - $pessimisticCost, 0),
            ],
        ];
    }
}

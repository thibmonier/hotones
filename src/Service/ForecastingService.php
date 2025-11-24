<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ProjectRepository;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class ForecastingService
{
    public function __construct(
        private ProjectRepository $projectRepository
    ) {
    }

    /**
     * Prévision du CA avec régression linéaire et saisonnalité.
     *
     * @param int $horizon Nombre de mois à prévoir (3, 6, ou 12)
     *
     * @return array{
     *     predictions: array<array{month: string, predicted: float, min: float, max: float}>,
     *     trend: string,
     *     confidence: float,
     *     historical: array<array{month: string, actual: float}>
     * }
     */
    public function forecastRevenue(int $horizon = 6): array
    {
        if (!in_array($horizon, [3, 6, 12], true)) {
            throw new InvalidArgumentException('Horizon must be 3, 6, or 12 months');
        }

        // 1. Récupérer l'historique (24 derniers mois minimum)
        $historical = $this->getHistoricalRevenue(24);

        if (count($historical) < 6) {
            throw new RuntimeException('Insufficient historical data for forecasting (minimum 6 months required)');
        }

        // 2. Calculer la tendance avec moyenne mobile pondérée
        $trend = $this->calculateWeightedMovingAverage($historical, 3);

        // 3. Calculer les coefficients de saisonnalité
        $seasonality = $this->calculateSeasonalityFactors($historical);

        // 4. Générer les prévisions
        $predictions = [];
        $lastMonth   = new DateTimeImmutable(end($historical)['month']);

        for ($i = 1; $i <= $horizon; ++$i) {
            $forecastMonth = $lastMonth->modify("+{$i} months");
            $monthKey      = (int) $forecastMonth->format('n');

            // Prévision de base (tendance)
            $baseForecast = $trend * (1 + ($i * 0.02)); // 2% de croissance mensuelle

            // Ajustement saisonnier
            $seasonalFactor = $seasonality[$monthKey] ?? 1.0;
            $predicted      = $baseForecast * $seasonalFactor;

            // Intervalles de confiance (±15% pour les 3 premiers mois, ±25% au-delà)
            $confidenceMargin = $i <= 3 ? 0.15 : 0.25;
            $predictions[]    = [
                'month'     => $forecastMonth->format('Y-m'),
                'predicted' => round($predicted, 2),
                'min'       => round($predicted * (1 - $confidenceMargin), 2),
                'max'       => round($predicted * (1 + $confidenceMargin), 2),
            ];
        }

        // 5. Calculer la fiabilité globale
        $confidence = $this->calculateConfidence($historical);

        // 6. Déterminer la tendance générale
        $trendDirection = $this->determineTrendDirection($historical);

        return [
            'predictions' => $predictions,
            'trend'       => $trendDirection,
            'confidence'  => round($confidence, 2),
            'historical'  => $historical,
        ];
    }

    /**
     * Récupère l'historique du CA mensuel.
     *
     * @return array<array{month: string, actual: float}>
     */
    private function getHistoricalRevenue(int $months): array
    {
        return $this->getRevenueFromProjects($months);
    }

    /**
     * Calcule le CA directement depuis les projets (fallback).
     *
     * @return array<array{month: string, actual: float}>
     */
    private function getRevenueFromProjects(int $months): array
    {
        $endDate   = new DateTime();
        $startDate = (clone $endDate)->modify("-{$months} months");

        $projects = $this->projectRepository->createQueryBuilder('p')
            ->select('p')
            ->where('p.startDate >= :startDate')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('startDate', $startDate)
            ->setParameter('statuses', ['in_progress', 'completed'])
            ->getQuery()
            ->getResult();

        // Grouper par mois
        $monthlyRevenue = [];
        foreach ($projects as $project) {
            $month = $project->getStartDate()?->format('Y-m');
            if (!$month) {
                continue;
            }
            if (!isset($monthlyRevenue[$month])) {
                $monthlyRevenue[$month] = 0;
            }
            $monthlyRevenue[$month] += $project->getTotalSoldAmount();
        }

        ksort($monthlyRevenue);

        $historical = [];
        foreach ($monthlyRevenue as $month => $revenue) {
            $historical[] = [
                'month'  => $month,
                'actual' => (float) $revenue,
            ];
        }

        return $historical;
    }

    /**
     * Calcule la moyenne mobile pondérée (les mois récents ont plus de poids).
     */
    private function calculateWeightedMovingAverage(array $historical, int $period): float
    {
        $count = count($historical);
        if ($count < $period) {
            $period = $count;
        }

        $recentData  = array_slice($historical, -$period);
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($recentData as $i => $data) {
            $weight = $i + 1; // Poids croissant : 1, 2, 3...
            $weightedSum += $data['actual'] * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    /**
     * Calcule les coefficients de saisonnalité (1 = moyenne, >1 = au-dessus, <1 = en dessous).
     *
     * @return array<int, float> Index = numéro du mois (1-12)
     */
    private function calculateSeasonalityFactors(array $historical): array
    {
        $monthlyTotals = array_fill(1, 12, []);

        foreach ($historical as $data) {
            $date                    = new DateTimeImmutable($data['month']);
            $month                   = (int) $date->format('n');
            $monthlyTotals[$month][] = $data['actual'];
        }

        // Calculer la moyenne globale
        $allValues     = array_merge(...array_values($monthlyTotals));
        $globalAverage = count($allValues) > 0 ? array_sum($allValues) / count($allValues) : 1;

        // Calculer le facteur pour chaque mois
        $seasonality = [];
        for ($month = 1; $month <= 12; ++$month) {
            if (empty($monthlyTotals[$month])) {
                $seasonality[$month] = 1.0; // Pas de données, facteur neutre
                continue;
            }

            $monthAverage        = array_sum($monthlyTotals[$month])  / count($monthlyTotals[$month]);
            $seasonality[$month] = $globalAverage > 0 ? $monthAverage / $globalAverage : 1.0;
        }

        return $seasonality;
    }

    /**
     * Calcule la fiabilité de la prévision (0-100%).
     */
    private function calculateConfidence(array $historical): float
    {
        $count = count($historical);

        // Confiance de base selon la quantité de données
        if ($count < 6) {
            return 40;
        }
        if ($count < 12) {
            return 60;
        }
        if ($count < 24) {
            return 75;
        }

        // Confiance réduite si forte volatilité
        $values   = array_column($historical, 'actual');
        $mean     = array_sum($values) / count($values);
        $variance = 0;
        foreach ($values as $value) {
            $variance += ($value - $mean) ** 2;
        }
        $stdDev                 = sqrt($variance / count($values));
        $coefficientOfVariation = $mean > 0 ? ($stdDev / $mean) : 0;

        // Plus la variation est faible, plus la confiance est élevée
        $confidenceAdjustment = max(0, 100 - ($coefficientOfVariation * 100));

        return min(95, 85 + ($confidenceAdjustment * 0.1)); // Maximum 95%
    }

    /**
     * Détermine la tendance générale (croissance/stable/décroissance).
     */
    private function determineTrendDirection(array $historical): string
    {
        $count = count($historical);
        if ($count < 3) {
            return 'insufficient_data';
        }

        // Comparer la moyenne des 3 derniers mois vs 3 mois précédents
        $recent   = array_slice($historical, -3);
        $previous = array_slice($historical, -6, 3);

        $recentAvg   = array_sum(array_column($recent, 'actual')) / count($recent);
        $previousAvg = count($previous) > 0
            ? array_sum(array_column($previous, 'actual')) / count($previous)
            : $recentAvg;

        if ($previousAvg == 0) {
            return 'stable';
        }

        $change = (($recentAvg - $previousAvg) / $previousAvg) * 100;

        if ($change > 10) {
            return 'growth';
        }
        if ($change < -10) {
            return 'decline';
        }

        return 'stable';
    }
}

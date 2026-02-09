<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FactForecast;
use App\Repository\FactForecastRepository;
use App\Repository\ProjectRepository;
use App\Security\CompanyContext;
use App\Service\Analytics\DashboardReadService;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;

class ForecastingService
{
    private const string SCENARIO_REALISTIC   = 'realistic';
    private const string SCENARIO_OPTIMISTIC  = 'optimistic';
    private const string SCENARIO_PESSIMISTIC = 'pessimistic';

    private const array SCENARIO_ADJUSTMENTS = [
        self::SCENARIO_OPTIMISTIC  => 1.10, // +10%
        self::SCENARIO_REALISTIC   => 1.00, // baseline
        self::SCENARIO_PESSIMISTIC => 0.85, // -15%
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CompanyContext $companyContext,
        private readonly FactForecastRepository $forecastRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly DashboardReadService $dashboardService,
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

        $projects = $this->projectRepository
            ->createQueryBuilder('p')
            ->select('p')
            ->where('p.startDate >= :startDate')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('startDate', $startDate)
            ->setParameter('statuses', ['in_progress', 'completed'])
            ->getQuery()
            ->getResult();

        // Grouper par mois
        $monthlyRevenue = [];
        if (!is_null($projects)) {
            foreach ($projects as $project) {
                $month = $project->startDate?->format('Y-m');
                if (!$month) {
                    continue;
                }
                if (!isset($monthlyRevenue[$month])) {
                    $monthlyRevenue[$month] = 0;
                }
                $monthlyRevenue[$month] += $project->getTotalSoldAmount();
            }
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
        $coefficientOfVariation = $mean > 0 ? $stdDev / $mean : 0;

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

        if ($previousAvg === 0 || $previousAvg === 0.0) {
            return $recentAvg > 0 ? 'growth' : 'stable';
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

    /**
     * Generate and persist forecasts for the next N months.
     *
     * @return FactForecast[] Generated forecasts for all scenarios
     */
    public function generateForecasts(int $months = 12): array
    {
        $forecasts = [];
        $now       = new DateTimeImmutable();

        // Pre-calculate historical data ONCE to avoid repeated expensive queries
        $historicalData = $this->preCalculateHistoricalData();

        for ($i = 1; $i <= $months; ++$i) {
            $periodStart = $now->modify("+{$i} months")->modify('first day of this month');
            $periodEnd   = $periodStart->modify('last day of this month');

            // Calculate base prediction ONCE per month
            $trendPrediction   = $this->calculateTrendPredictionFromCache($periodStart, $historicalData);
            $seasonalityFactor = $this->calculateSeasonalityFromCache($periodStart, $historicalData);

            $basePrediction = ($trendPrediction * 0.70) + ($trendPrediction * $seasonalityFactor * 0.30);

            foreach (self::SCENARIO_ADJUSTMENTS as $scenario => $adjustment) {
                $forecast = $this->createForecastFromBase(
                    $periodStart,
                    $periodEnd,
                    $scenario,
                    $adjustment,
                    $basePrediction,
                    $trendPrediction,
                    $seasonalityFactor,
                );
                $forecasts[] = $forecast;
                $this->em->persist($forecast);
            }
        }

        $this->em->flush();

        return $forecasts;
    }

    /**
     * Pre-calculate all historical data needed for forecasting.
     * This avoids repeated expensive database queries.
     *
     * Simplified version: Uses only 6 months for trend and 1 year for seasonality
     * to reduce computation time while maintaining reasonable accuracy.
     *
     * @return array{trend: array, seasonality: array}
     */
    private function preCalculateHistoricalData(): array
    {
        $historicalMonths = 6; // Reduced from 12 for performance
        $trendData        = [];
        $seasonalityData  = [];

        // Collect last 6 months for trend analysis (sufficient for short-term forecasting)
        for ($i = $historicalMonths; $i >= 1; --$i) {
            $month     = new DateTimeImmutable()->modify("-{$i} months");
            $startDate = $month->modify('first day of this month');
            $endDate   = $month->modify('last day of this month');

            $metrics = $this->dashboardService->getKPIs($startDate, $endDate);
            $revenue = (float) ($metrics['revenue'] ?? 0);

            $trendData[] = [
                'x'     => $historicalMonths - $i + 1,
                'y'     => $revenue,
                'month' => $month,
            ];
        }

        // Collect last 12 months for seasonality analysis (1 year instead of 3)
        // This is much faster and still captures seasonal patterns
        for ($i = 12; $i >= 1; --$i) {
            $month     = new DateTimeImmutable()->modify("-{$i} months");
            $monthNum  = (int) $month->format('m');
            $startDate = $month->modify('first day of this month');
            $endDate   = $month->modify('last day of this month');

            $metrics = $this->dashboardService->getKPIs($startDate, $endDate);
            if (isset($metrics['revenue'])) {
                $seasonalityData[$monthNum] = (float) $metrics['revenue'];
            }
        }

        // Calculate year average for seasonality normalization
        $yearAverage = !empty($seasonalityData) ? array_sum($seasonalityData) / count($seasonalityData) : 1.0;

        return [
            'trend'       => $trendData,
            'seasonality' => $seasonalityData,
            'yearAverage' => $yearAverage,
        ];
    }

    /**
     * Calculate trend prediction using pre-calculated historical data.
     */
    private function calculateTrendPredictionFromCache(DateTimeImmutable $targetMonth, array $historicalData): float
    {
        $dataPoints = array_map(fn ($item): array => ['x' => $item['x'], 'y' => $item['y']], $historicalData['trend']);

        $regression  = $this->linearRegressionSimple($dataPoints);
        $monthsAhead = $this->getMonthsDifference(new DateTimeImmutable(), $targetMonth);

        $prediction = ($regression['slope'] * (count($dataPoints) + $monthsAhead)) + $regression['intercept'];

        return max(0, $prediction);
    }

    /**
     * Calculate seasonality factor using pre-calculated historical data.
     */
    private function calculateSeasonalityFromCache(DateTimeImmutable $targetMonth, array $historicalData): float
    {
        $targetMonthNum  = (int) $targetMonth->format('m');
        $seasonalityData = $historicalData['seasonality'];
        $yearAverage     = $historicalData['yearAverage'];

        if (!isset($seasonalityData[$targetMonthNum]) || $yearAverage <= 0) {
            return 1.0;
        }

        return $seasonalityData[$targetMonthNum] / $yearAverage;
    }

    /**
     * Create forecast from pre-calculated base prediction.
     */
    private function createForecastFromBase(
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        string $scenario,
        float $scenarioAdjustment,
        float $basePrediction,
        float $trendPrediction,
        float $seasonalityFactor,
    ): FactForecast {
        // Apply scenario adjustment
        $predictedRevenue = bcmul((string) $basePrediction, (string) $scenarioAdjustment, 2);

        // Calculate confidence intervals
        $confidenceRange = $scenario === self::SCENARIO_REALISTIC ? 0.15 : 0.25;
        $confidenceMin   = bcmul($predictedRevenue, (string) (1 - $confidenceRange), 2);
        $confidenceMax   = bcmul($predictedRevenue, (string) (1 + $confidenceRange), 2);

        $forecast = new FactForecast();
        $forecast->setCompany($this->companyContext->getCurrentCompany());
        $forecast->setPeriodStart($periodStart);
        $forecast->setPeriodEnd($periodEnd);
        $forecast->setScenario($scenario);
        $forecast->setPredictedRevenue($predictedRevenue);
        $forecast->setConfidenceMin($confidenceMin);
        $forecast->setConfidenceMax($confidenceMax);
        $forecast->setMetadata([
            'trend_prediction'   => $trendPrediction,
            'seasonality_factor' => $seasonalityFactor,
            'method'             => 'hybrid_linear_regression_seasonality',
        ]);

        return $forecast;
    }

    /**
     * Simple linear regression: y = mx + b.
     *
     * @param array<array{x: int|float, y: float}> $points
     *
     * @return array{slope: float, intercept: float}
     */
    private function linearRegressionSimple(array $points): array
    {
        $n = count($points);
        if ($n < 2) {
            return ['slope' => 0.0, 'intercept' => 0.0];
        }

        $sumX  = 0;
        $sumY  = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($points as $point) {
            $sumX  += $point['x'];
            $sumY  += $point['y'];
            $sumXY += $point['x'] * $point['y'];
            $sumX2 += $point['x'] * $point['x'];
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator === 0) {
            return ['slope' => 0.0, 'intercept' => $sumY / $n];
        }

        $slope     = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX))        / $n;

        return ['slope' => $slope, 'intercept' => $intercept];
    }

    /**
     * Calculate difference in months between two dates.
     */
    private function getMonthsDifference(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $diff = $from->diff($to);

        return ($diff->y * 12) + $diff->m;
    }

    /**
     * Update forecast accuracy by comparing predictions with actual data.
     */
    public function updateForecastAccuracy(DateTimeImmutable $period): void
    {
        $periodStart = $period->modify('first day of this month');
        $periodEnd   = $period->modify('last day of this month');

        // Get actual revenue for the period
        $metrics       = $this->dashboardService->getKPIs($periodStart, $periodEnd);
        $actualRevenue = (string) ($metrics['revenue'] ?? 0);

        // Update all forecasts for this period
        foreach (self::SCENARIO_ADJUSTMENTS as $scenario => $adjustment) {
            $forecast = $this->forecastRepository->findLatestForPeriod($periodStart, $periodEnd, $scenario);

            if ($forecast) {
                $forecast->setActualRevenue($actualRevenue);

                // Calculate accuracy: (1 - |predicted - actual| / actual) * 100
                $predicted = (float) $forecast->getPredictedRevenue();
                $actual    = (float) $actualRevenue;

                if ($actual > 0) {
                    $error    = abs($predicted - $actual) / $actual;
                    $accuracy = bcmul((string) (1 - $error), '100', 2);
                    $forecast->setAccuracy($accuracy);
                }

                $this->em->flush();
            }
        }
    }
}

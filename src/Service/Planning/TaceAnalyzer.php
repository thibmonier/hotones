<?php

declare(strict_types=1);

namespace App\Service\Planning;

use App\Entity\Contributor;
use App\Repository\ContributorRepository;
use App\Repository\StaffingMetricsRepository;
use DateTime;

/**
 * Analyse le TACE des contributeurs et identifie les problèmes de charge.
 */
class TaceAnalyzer
{
    // Seuils de TACE (en pourcentage)
    private const TACE_IDEAL_MIN     = 70;  // En dessous = sous-utilisation
    private const TACE_IDEAL_MAX     = 90;  // Au-dessus = surcharge
    private const TACE_CRITICAL_LOW  = 50;  // Sous-utilisation critique
    private const TACE_CRITICAL_HIGH = 110; // Surcharge critique

    public function __construct(
        private readonly ContributorRepository $contributorRepository,
        private readonly StaffingMetricsRepository $staffingMetricsRepository
    ) {
    }

    /**
     * Analyse le TACE de tous les contributeurs actifs pour une période donnée.
     *
     * @return array{overloaded: array, underutilized: array, optimal: array, critical: array}
     */
    public function analyzeAllContributors(?DateTime $startDate = null, ?DateTime $endDate = null): array
    {
        if (!$startDate) {
            $startDate = new DateTime('first day of this month');
        }
        if (!$endDate) {
            $endDate = new DateTime('last day of this month');
        }

        $contributors = $this->contributorRepository->findBy(['active' => true]);
        $results      = [
            'overloaded'    => [], // TACE > TACE_IDEAL_MAX
            'underutilized' => [], // TACE < TACE_IDEAL_MIN
            'optimal'       => [], // TACE entre MIN et MAX
            'critical'      => [], // TACE < CRITICAL_LOW ou > CRITICAL_HIGH
        ];

        foreach ($contributors as $contributor) {
            $analysis = $this->analyzeContributor($contributor, $startDate, $endDate);

            if ($analysis['tace'] !== null) {
                $analysis['contributor'] = $contributor;

                // Classement critique en priorité
                if ($analysis['status'] === 'critical_high' || $analysis['status'] === 'critical_low') {
                    $results['critical'][] = $analysis;
                } elseif ($analysis['status'] === 'overloaded') {
                    $results['overloaded'][] = $analysis;
                } elseif ($analysis['status'] === 'underutilized') {
                    $results['underutilized'][] = $analysis;
                } else {
                    $results['optimal'][] = $analysis;
                }
            }
        }

        // Trier par gravité (TACE le plus éloigné de l'idéal)
        usort($results['critical'], fn ($a, $b) => abs($b['tace'] - 80) <=> abs($a['tace'] - 80));
        usort($results['overloaded'], fn ($a, $b) => $b['tace'] <=> $a['tace']);
        usort($results['underutilized'], fn ($a, $b) => $a['tace'] <=> $b['tace']);

        return $results;
    }

    /**
     * Analyse le TACE d'un contributeur pour une période donnée.
     */
    public function analyzeContributor(Contributor $contributor, DateTime $startDate, DateTime $endDate): array
    {
        // Récupérer les métriques de staffing pour la période
        $metrics = $this->staffingMetricsRepository->findByPeriod(
            $startDate,
            $endDate,
            'weekly',
            null,
            $contributor,
        );

        if (empty($metrics)) {
            return [
                'tace'            => null,
                'availability'    => 0,
                'workload'        => 0,
                'status'          => 'no_data',
                'severity'        => 0,
                'deviation'       => 0,
                'recommendations' => [],
            ];
        }

        // Calculer le TACE moyen sur la période
        $totalTace         = 0;
        $totalAvailability = 0;
        $totalWorkload     = 0;
        $count             = 0;

        foreach ($metrics as $metric) {
            $totalTace         += (float) $metric->getTace();
            $totalAvailability += (float) $metric->getAvailableDays();
            $totalWorkload     += (float) $metric->getWorkedDays();
            ++$count;
        }

        $avgTace         = $totalTace         / $count;
        $avgAvailability = $totalAvailability / $count;
        $avgWorkload     = $totalWorkload     / $count;

        // Déterminer le statut
        $status    = $this->determineStatus($avgTace);
        $severity  = $this->calculateSeverity($avgTace);
        $deviation = $this->calculateDeviation($avgTace);

        return [
            'tace'         => round($avgTace, 2),
            'availability' => round($avgAvailability, 2),
            'workload'     => round($avgWorkload, 2),
            'status'       => $status,
            'severity'     => $severity,
            'deviation'    => $deviation,
            'period_start' => $startDate,
            'period_end'   => $endDate,
        ];
    }

    /**
     * Détermine le statut en fonction du TACE.
     */
    private function determineStatus(float $tace): string
    {
        if ($tace >= self::TACE_CRITICAL_HIGH) {
            return 'critical_high';
        }
        if ($tace <= self::TACE_CRITICAL_LOW) {
            return 'critical_low';
        }
        if ($tace > self::TACE_IDEAL_MAX) {
            return 'overloaded';
        }
        if ($tace < self::TACE_IDEAL_MIN) {
            return 'underutilized';
        }

        return 'optimal';
    }

    /**
     * Calcule la sévérité du problème (0-100).
     */
    private function calculateSeverity(float $tace): int
    {
        $idealCenter = (self::TACE_IDEAL_MIN + self::TACE_IDEAL_MAX) / 2; // 80%
        $deviation   = abs($tace - $idealCenter);

        // Normaliser sur une échelle de 0-100
        // Plus on s'éloigne de l'idéal, plus c'est sévère
        return min(100, (int) ($deviation * 2));
    }

    /**
     * Calcule la déviation par rapport à l'idéal (en points de pourcentage).
     */
    private function calculateDeviation(float $tace): float
    {
        $idealCenter = (self::TACE_IDEAL_MIN + self::TACE_IDEAL_MAX) / 2;

        return round($tace - $idealCenter, 2);
    }

    /**
     * Obtient la configuration des seuils.
     */
    public function getThresholds(): array
    {
        return [
            'ideal_min'     => self::TACE_IDEAL_MIN,
            'ideal_max'     => self::TACE_IDEAL_MAX,
            'critical_low'  => self::TACE_CRITICAL_LOW,
            'critical_high' => self::TACE_CRITICAL_HIGH,
        ];
    }
}

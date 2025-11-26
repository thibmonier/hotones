<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CompanySettingsRepository;
use DateInterval;
use DatePeriod;
use DateTime;

/**
 * Service de calcul du CJM (Coût Journalier Moyen).
 */
class CjmCalculatorService
{
    public function __construct(
        private readonly CompanySettingsRepository $companySettingsRepository
    ) {
    }

    /**
     * Calcule le nombre de jours ouvrés dans une année.
     *
     * Formule : jours de l'année - weekends - jours fériés - CP - RTT
     *
     * @param int $year Année de référence
     */
    public function calculateWorkingDaysInYear(int $year): int
    {
        $settings = $this->companySettingsRepository->getSettings();

        // Nombre total de jours dans l'année
        $totalDays = (new DateTime("$year-12-31"))->format('z') + 1;

        // Calculer le nombre de weekends
        $weekends = $this->countWeekendsInYear($year);

        // Calculer le nombre de jours fériés (hors weekends)
        $publicHolidays = $this->countPublicHolidaysInYear($year);

        // Jours ouvrés = Total - Weekends - Fériés - CP - RTT
        $workingDays = $totalDays
            - $weekends
            - $publicHolidays
            - $settings->getAnnualPaidLeaveDays()
            - $settings->getAnnualRttDays();

        return max($workingDays, 1); // Minimum 1 jour
    }

    /**
     * Compte le nombre de jours de weekend dans une année.
     */
    private function countWeekendsInYear(int $year): int
    {
        $weekendDays = 0;
        $start       = new DateTime("$year-01-01");
        $end         = new DateTime("$year-12-31");

        $interval = new DateInterval('P1D');
        $period   = new DatePeriod($start, $interval, $end->modify('+1 day'));

        foreach ($period as $date) {
            $dayOfWeek = (int) $date->format('N'); // 1 (lundi) à 7 (dimanche)
            if ($dayOfWeek === 6 || $dayOfWeek === 7) { // Samedi ou dimanche
                ++$weekendDays;
            }
        }

        return $weekendDays;
    }

    /**
     * Compte le nombre de jours fériés dans une année (hors weekends).
     *
     * Jours fériés en France :
     * - 1er janvier (Jour de l'an)
     * - Lundi de Pâques
     * - 1er mai (Fête du travail)
     * - 8 mai (Victoire 1945)
     * - Ascension (39 jours après Pâques)
     * - Lundi de Pentecôte (50 jours après Pâques)
     * - 14 juillet (Fête nationale)
     * - 15 août (Assomption)
     * - 1er novembre (Toussaint)
     * - 11 novembre (Armistice 1918)
     * - 25 décembre (Noël)
     */
    private function countPublicHolidaysInYear(int $year): int
    {
        $holidays = $this->getPublicHolidays($year);
        $count    = 0;

        foreach ($holidays as $holiday) {
            $dayOfWeek = (int) $holiday->format('N');
            // Ne compter que si ce n'est pas un weekend
            if ($dayOfWeek < 6) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Retourne la liste des jours fériés pour une année donnée.
     *
     * @return array<DateTime>
     */
    private function getPublicHolidays(int $year): array
    {
        $easter = $this->getEasterDate($year);

        return [
            new DateTime("$year-01-01"), // Jour de l'an
            (clone $easter)->modify('+1 day'), // Lundi de Pâques
            new DateTime("$year-05-01"), // Fête du travail
            new DateTime("$year-05-08"), // Victoire 1945
            (clone $easter)->modify('+39 days'), // Ascension
            (clone $easter)->modify('+50 days'), // Lundi de Pentecôte
            new DateTime("$year-07-14"), // Fête nationale
            new DateTime("$year-08-15"), // Assomption
            new DateTime("$year-11-01"), // Toussaint
            new DateTime("$year-11-11"), // Armistice 1918
            new DateTime("$year-12-25"), // Noël
        ];
    }

    /**
     * Calcule la date de Pâques pour une année donnée
     * Utilise l'algorithme de Meeus/Jones/Butcher.
     */
    private function getEasterDate(int $year): DateTime
    {
        $a     = $year % 19;
        $b     = (int) ($year / 100);
        $c     = $year % 100;
        $d     = (int) ($b / 4);
        $e     = $b % 4;
        $f     = (int) (($b + 8) / 25);
        $g     = (int) (($b - $f + 1) / 3);
        $h     = (19 * $a + $b - $d - $g + 15) % 30;
        $i     = (int) ($c / 4);
        $k     = $c                               % 4;
        $l     = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m     = (int) (($a + 11 * $h + 22 * $l) / 451);
        $month = (int) (($h + $l - 7 * $m + 114) / 31);
        $day   = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new DateTime(sprintf('%d-%02d-%02d', $year, $month, $day));
    }

    /**
     * Calcule le CJM (Coût Journalier Moyen) à partir d'un salaire mensuel brut.
     *
     * Formule : CJM = (salaire mensuel × 12 × coefficient global) / jours ouvrés annuels
     *
     * @param string $monthlySalary Salaire mensuel brut
     * @param int    $year          Année de référence pour le calcul des jours ouvrés
     *
     * @return string CJM arrondi à 2 décimales
     */
    public function calculateCjmFromMonthlySalary(string $monthlySalary, int $year): string
    {
        $settings = $this->companySettingsRepository->getSettings();

        // Salaire annuel brut
        $annualSalary = bcmul($monthlySalary, '12', 2);

        // Coefficient de charge global
        $globalCoefficient = $settings->getGlobalChargeCoefficient();

        // Coût annuel total pour l'entreprise
        $annualCost = bcmul($annualSalary, $globalCoefficient, 2);

        // Nombre de jours ouvrés
        $workingDays = $this->calculateWorkingDaysInYear($year);

        // CJM = coût annuel / jours ouvrés
        $cjm = bcdiv($annualCost, (string) $workingDays, 2);

        return $cjm;
    }

    /**
     * Retourne un rapport détaillé du calcul pour une année.
     *
     * @return array{
     *     year: int,
     *     total_days: int,
     *     weekends: int,
     *     public_holidays: int,
     *     paid_leave: int,
     *     rtt: int,
     *     working_days: int,
     *     structure_coefficient: string,
     *     employer_charges_coefficient: string,
     *     global_coefficient: string
     * }
     */
    public function getCalculationReport(int $year): array
    {
        $settings       = $this->companySettingsRepository->getSettings();
        $totalDays      = (new DateTime("$year-12-31"))->format('z') + 1;
        $weekends       = $this->countWeekendsInYear($year);
        $publicHolidays = $this->countPublicHolidaysInYear($year);

        return [
            'year'                         => $year,
            'total_days'                   => $totalDays,
            'weekends'                     => $weekends,
            'public_holidays'              => $publicHolidays,
            'paid_leave'                   => $settings->getAnnualPaidLeaveDays(),
            'rtt'                          => $settings->getAnnualRttDays(),
            'working_days'                 => $this->calculateWorkingDaysInYear($year),
            'structure_coefficient'        => $settings->getStructureCostCoefficient(),
            'employer_charges_coefficient' => $settings->getEmployerChargesCoefficient(),
            'global_coefficient'           => $settings->getGlobalChargeCoefficient(),
        ];
    }
}

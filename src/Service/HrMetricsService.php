<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ContributorRepository;
use App\Repository\EmploymentPeriodRepository;
use App\Repository\VacationRepository;
use DateTime;
use DateTimeInterface;

class HrMetricsService
{
    public function __construct(
        public ContributorRepository $contributorRepository,
        private EmploymentPeriodRepository $employmentPeriodRepository,
        private VacationRepository $vacationRepository
    ) {
    }

    /**
     * Calcule le turnover sur une période donnée.
     * Turnover = (Nombre de départs / Effectif moyen) * 100.
     *
     * @return array{turnoverRate: float, departures: int, averageHeadcount: float}
     */
    public function calculateTurnover(DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        // Compter les départs (périodes d'emploi qui se terminent dans la période)
        $departuresCount = $this->employmentPeriodRepository->countDepartures($startDate, $endDate);

        // Effectif moyen = (effectif début + effectif fin) / 2
        $headcountStart   = $this->employmentPeriodRepository->countActiveAt($startDate);
        $headcountEnd     = $this->employmentPeriodRepository->countActiveAt($endDate);
        $averageHeadcount = ($headcountStart + $headcountEnd) / 2;

        $turnoverRate = $averageHeadcount > 0 ? ($departuresCount / $averageHeadcount) * 100 : 0;

        return [
            'turnoverRate'     => round($turnoverRate, 2),
            'departures'       => $departuresCount,
            'averageHeadcount' => round($averageHeadcount, 1),
            'headcountStart'   => $headcountStart,
            'headcountEnd'     => $headcountEnd,
        ];
    }

    /**
     * Calcule le taux d'absentéisme sur une période.
     * Absentéisme = (Jours d'absence / Jours travaillés théoriques) * 100.
     *
     * @return array{absenteeismRate: float, absentDays: float, theoreticalDays: float}
     */
    public function calculateAbsenteeism(DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        // Jours d'absence = congés + absences non justifiées
        $vacationDays = $this->vacationRepository->countApprovedDaysBetween($startDate, $endDate);

        // Jours travaillés théoriques = effectif moyen * jours ouvrés dans la période
        $workingDays      = $this->countWorkingDays($startDate, $endDate);
        $averageHeadcount = ($this->employmentPeriodRepository->countActiveAt($startDate)
            + $this->employmentPeriodRepository->countActiveAt($endDate)) / 2;

        $theoreticalDays = $averageHeadcount                                         * $workingDays;
        $absenteeismRate = $theoreticalDays > 0 ? ($vacationDays / $theoreticalDays) * 100 : 0;

        return [
            'absenteeismRate' => round($absenteeismRate, 2),
            'absentDays'      => $vacationDays,
            'theoreticalDays' => round($theoreticalDays, 1),
            'workingDays'     => $workingDays,
        ];
    }

    /**
     * Calcule l'ancienneté moyenne des contributeurs.
     *
     * @return array{averageSeniority: float, bySeniority: array}
     */
    public function calculateAverageSeniority(): array
    {
        $activeContributors = $this->contributorRepository->findBy(['active' => true]);

        if (count($activeContributors) === 0) {
            return [
                'averageSeniority' => 0,
                'bySeniority'      => [],
                'totalActive'      => 0,
            ];
        }

        $totalSeniority   = 0;
        $bySeniorityRange = [
            '< 1 an'   => 0,
            '1-2 ans'  => 0,
            '2-5 ans'  => 0,
            '5-10 ans' => 0,
            '> 10 ans' => 0,
        ];

        $now = new DateTime();

        foreach ($activeContributors as $contributor) {
            $firstPeriod = $this->employmentPeriodRepository->findFirstByContributor($contributor);
            if (!$firstPeriod) {
                continue;
            }

            $yearsOfService = $firstPeriod->startDate->diff($now)->days / 365;
            $totalSeniority += $yearsOfService;

            // Catégoriser
            if ($yearsOfService < 1) {
                ++$bySeniorityRange['< 1 an'];
            } elseif ($yearsOfService < 2) {
                ++$bySeniorityRange['1-2 ans'];
            } elseif ($yearsOfService < 5) {
                ++$bySeniorityRange['2-5 ans'];
            } elseif ($yearsOfService < 10) {
                ++$bySeniorityRange['5-10 ans'];
            } else {
                ++$bySeniorityRange['> 10 ans'];
            }
        }

        $averageSeniority = $totalSeniority / count($activeContributors);

        return [
            'averageSeniority' => round($averageSeniority, 1),
            'bySeniority'      => $bySeniorityRange,
            'totalActive'      => count($activeContributors),
        ];
    }

    /**
     * Génère la pyramide des âges avec répartition par genre.
     *
     * @return array{ageRanges: array, averageAge: float, byGender: array, parityRate: float}
     */
    public function getAgePyramid(): array
    {
        $activeContributors = $this->contributorRepository->findBy(['active' => true]);

        if (count($activeContributors) === 0) {
            return [
                'ageRanges'   => [],
                'averageAge'  => 0,
                'totalActive' => 0,
                'byGender'    => ['male' => 0, 'female' => 0, 'other' => 0],
                'parityRate'  => 0,
            ];
        }

        $ageRanges = [
            '< 25 ans'  => 0,
            '25-30 ans' => 0,
            '30-40 ans' => 0,
            '40-50 ans' => 0,
            '50-60 ans' => 0,
            '> 60 ans'  => 0,
        ];

        // Structure pour la pyramide par genre
        $ageRangesByGender = [
            'male'   => ['< 25 ans' => 0, '25-30 ans' => 0, '30-40 ans' => 0, '40-50 ans' => 0, '50-60 ans' => 0, '> 60 ans' => 0],
            'female' => ['< 25 ans' => 0, '25-30 ans' => 0, '30-40 ans' => 0, '40-50 ans' => 0, '50-60 ans' => 0, '> 60 ans' => 0],
            'other'  => ['< 25 ans' => 0, '25-30 ans' => 0, '30-40 ans' => 0, '40-50 ans' => 0, '50-60 ans' => 0, '> 60 ans' => 0],
        ];

        $genderCounts = ['male' => 0, 'female' => 0, 'other' => 0];
        $totalAge     = 0;
        $count        = 0;
        $now          = new DateTime();

        foreach ($activeContributors as $contributor) {
            $age = $contributor->getAge($now);
            if ($age === null) {
                continue; // Skip contributors without birthDate
            }

            $totalAge += $age;
            ++$count;

            // Déterminer la tranche d'âge
            if ($age < 25) {
                $range = '< 25 ans';
            } elseif ($age < 30) {
                $range = '25-30 ans';
            } elseif ($age < 40) {
                $range = '30-40 ans';
            } elseif ($age < 50) {
                $range = '40-50 ans';
            } elseif ($age < 60) {
                $range = '50-60 ans';
            } else {
                $range = '> 60 ans';
            }

            ++$ageRanges[$range];

            // Compter par genre
            $gender = $contributor->gender ?? 'other';
            if (isset($genderCounts[$gender])) {
                ++$genderCounts[$gender];
                ++$ageRangesByGender[$gender][$range];
            }
        }

        $averageAge = $count > 0 ? $totalAge / $count : 0;

        // Calcul du taux de parité (% femmes parmi homme + femme)
        $totalGendered = $genderCounts['male'] + $genderCounts['female'];
        $parityRate    = $totalGendered > 0 ? ($genderCounts['female'] / $totalGendered) * 100 : 0;

        return [
            'ageRanges'          => $ageRanges,
            'averageAge'         => round($averageAge, 1),
            'totalActive'        => count($activeContributors),
            'byGender'           => $genderCounts,
            'ageByGender'        => $ageRangesByGender,
            'parityRate'         => round($parityRate, 1),
            'countWithBirthDate' => $count,
        ];
    }

    /**
     * Génère la pyramide des compétences (répartition par profil).
     *
     * @return array{byProfile: array, totalActive: int}
     */
    public function getSkillsPyramid(): array
    {
        $activeContributors = $this->contributorRepository->findBy(['active' => true]);

        $byProfile = [];
        foreach ($activeContributors as $contributor) {
            $currentPeriod = $contributor->getCurrentEmploymentPeriod();
            if (!$currentPeriod) {
                continue;
            }

            foreach ($currentPeriod->getProfiles() as $profile) {
                $profileName = $profile->getName();
                if (!isset($byProfile[$profileName])) {
                    $byProfile[$profileName] = 0;
                }
                ++$byProfile[$profileName];
            }
        }

        // Trier par nombre décroissant
        arsort($byProfile);

        return [
            'byProfile'   => $byProfile,
            'totalActive' => count($activeContributors),
        ];
    }

    /**
     * Retourne les KPIs RH globaux.
     *
     * @return array{turnover: array, absenteeism: array, seniority: array, agePyramid: array, skillsPyramid: array}
     */
    public function getAllMetrics(DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        return [
            'turnover'      => $this->calculateTurnover($startDate, $endDate),
            'absenteeism'   => $this->calculateAbsenteeism($startDate, $endDate),
            'seniority'     => $this->calculateAverageSeniority(),
            'agePyramid'    => $this->getAgePyramid(),
            'skillsPyramid' => $this->getSkillsPyramid(),
        ];
    }

    /**
     * Compte le nombre de jours ouvrés entre deux dates (du lundi au vendredi).
     */
    private function countWorkingDays(DateTimeInterface $startDate, DateTimeInterface $endDate): int
    {
        $workingDays = 0;
        $current     = (clone $startDate);
        $end         = (clone $endDate);

        while ($current <= $end) {
            $dayOfWeek = (int) $current->format('N'); // 1 (lundi) à 7 (dimanche)
            if ($dayOfWeek <= 5) { // Lundi à vendredi
                ++$workingDays;
            }
            $current->modify('+1 day');
        }

        return $workingDays;
    }
}

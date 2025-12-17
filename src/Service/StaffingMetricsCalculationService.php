<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Analytics\DimProfile;
use App\Entity\Analytics\DimTime;
use App\Entity\Analytics\FactStaffingMetrics;
use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use App\Repository\ContributorRepository;
use App\Repository\TimesheetRepository;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

/**
 * Service de calcul des métriques de staffing.
 * Calcule le taux de staffing et le TACE pour les contributeurs.
 */
class StaffingMetricsCalculationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContributorRepository $contributorRepo,
        private TimesheetRepository $timesheetRepo
    ) {
    }

    /**
     * Calcule et enregistre les métriques de staffing pour une période donnée.
     *
     * @param DateTimeInterface $startDate   Date de début
     * @param DateTimeInterface $endDate     Date de fin
     * @param string            $granularity Granularité (monthly par défaut)
     */
    public function calculateAndStoreMetrics(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'monthly'
    ): int {
        $metricsCreated = 0;

        // Générer les périodes selon la granularité
        $periods = $this->generatePeriods($startDate, $endDate, $granularity);

        foreach ($periods as $period) {
            // Récupérer ou créer la dimension temporelle
            $dimTime = $this->getOrCreateDimTime($period);

            // Calculer le début et la fin de la période selon la granularité
            $periodStart = clone $period;
            $periodEnd   = $this->calculatePeriodEnd($period, $granularity);

            // Récupérer tous les contributeurs actifs pendant la période
            $contributors = $this->contributorRepo->findActiveContributors();

            foreach ($contributors as $contributor) {
                // Récupérer la période d'emploi active pour ce contributeur
                $employmentPeriod = $this->getActiveEmploymentPeriod($contributor, $period);

                // Calculer les métriques pour ce contributeur sur cette période
                $metrics = $this->calculateMetricsForContributor($contributor, $periodStart, $periodEnd, $employmentPeriod);

                // Créer l'entrée de fait
                $fact = new FactStaffingMetrics();
                $fact->setDimTime($dimTime);
                $fact->setContributor($contributor);
                $fact->setAvailableDays((string) $metrics['availableDays']);
                $fact->setWorkedDays((string) $metrics['workedDays']);
                $fact->setStaffedDays((string) $metrics['staffedDays']);
                $fact->setVacationDays((string) $metrics['vacationDays']);
                $fact->setPlannedDays((string) $metrics['plannedDays']);
                $fact->setContributorCount(1);
                $fact->setGranularity($granularity);
                $fact->calculateMetrics(); // Calcule staffingRate et TACE

                // Associer le profil si disponible (seulement si periode d'emploi existe)
                if ($employmentPeriod) {
                    $dimProfile = $this->getOrCreateDimProfile($employmentPeriod);
                    if ($dimProfile) {
                        $fact->setDimProfile($dimProfile);
                    }
                }

                $this->entityManager->persist($fact);
                ++$metricsCreated;

                // Flush par batch
                if ($metricsCreated % 50 === 0) {
                    $this->entityManager->flush();
                }
            }
        }

        // Flush final
        $this->entityManager->flush();

        return $metricsCreated;
    }

    /**
     * Calcule les métriques pour un contributeur sur une période donnée.
     *
     * @param Contributor           $contributor      Contributeur
     * @param DateTimeInterface     $periodStart      Début de la période
     * @param DateTimeInterface     $periodEnd        Fin de la période
     * @param EmploymentPeriod|null $employmentPeriod Période d'emploi (null si aucune)
     *
     * @return array{availableDays: float, workedDays: float, staffedDays: float, vacationDays: float, plannedDays: float}
     */
    private function calculateMetricsForContributor(
        Contributor $contributor,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd,
        ?EmploymentPeriod $employmentPeriod
    ): array {
        // 1. Calculer les jours ouvrés disponibles dans la période (hors week-ends)
        $totalWorkingDays = $this->calculateWorkingDays($periodStart, $periodEnd);

        // 2. Calculer les jours de congés sur la période
        $vacationDays = $this->calculateVacationDays($contributor, $periodStart, $periodEnd);

        // 3. Jours disponibles = Jours ouvrés - Congés
        $availableDays = $totalWorkingDays - $vacationDays;

        // 4. Jours travaillés = Jours disponibles (car on exclut les congés)
        $workedDays = $availableDays;

        // 5. Calculer les jours staffés (temps passé sur missions client)
        $staffedDays = $this->calculateStaffedDays($contributor, $periodStart, $periodEnd);

        // 6. Calculer les jours planifiés (pour le futur)
        $plannedDays = $this->calculatePlannedDays($contributor, $periodStart, $periodEnd);

        return [
            'availableDays' => max(0, $availableDays),
            'workedDays'    => max(0, $workedDays),
            'staffedDays'   => max(0, $staffedDays),
            'vacationDays'  => $vacationDays,
            'plannedDays'   => $plannedDays,
        ];
    }

    /**
     * Calcule le nombre de jours ouvrés entre deux dates.
     */
    private function calculateWorkingDays(DateTimeInterface $start, DateTimeInterface $end): float
    {
        $days    = 0;
        $current = clone $start;

        while ($current <= $end) {
            // Exclure samedi (6) et dimanche (0)
            if (!in_array((int) $current->format('w'), [0, 6], true)) {
                ++$days;
            }
            $current->modify('+1 day');
        }

        return (float) $days;
    }

    /**
     * Calcule les jours de congés pour un contributeur sur une période.
     */
    private function calculateVacationDays(
        Contributor $contributor,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): float {
        $vacations = $this->entityManager->getRepository(\App\Entity\Vacation::class)
            ->createQueryBuilder('v')
            ->where('v.contributor = :contributor')
            ->andWhere('v.startDate <= :end')
            ->andWhere('v.endDate >= :start')
            ->andWhere('v.status = :status')
            ->setParameter('contributor', $contributor)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', 'approved')
            ->getQuery()
            ->getResult();

        $totalVacationDays = 0.0;

        foreach ($vacations as $vacation) {
            // Calculer l'intersection entre la vacation et la période
            $vacStart = max($vacation->getStartDate(), $start);
            $vacEnd   = min($vacation->getEndDate(), $end);

            // Compter les jours ouvrés
            $totalVacationDays += $this->calculateWorkingDays($vacStart, $vacEnd);
        }

        return $totalVacationDays;
    }

    /**
     * Calcule les jours staffés (temps passé sur missions) pour un contributeur.
     */
    private function calculateStaffedDays(
        Contributor $contributor,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): float {
        // Récupérer tous les timesheets du contributeur sur la période
        $timesheets = $this->timesheetRepo->findByContributorAndDateRange($contributor, $start, $end);

        $totalHours = 0.0;

        foreach ($timesheets as $timesheet) {
            $totalHours += (float) $timesheet->getHours();
        }

        // Convertir les heures en jours (1 jour = 8 heures)
        return $totalHours / 8.0;
    }

    /**
     * Calcule les jours planifiés (planification future) pour un contributeur.
     */
    private function calculatePlannedDays(
        Contributor $contributor,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): float {
        // Récupérer les planifications du contributeur sur la période (statut planned ou confirmed)
        $plannings = $this->entityManager->getRepository(\App\Entity\Planning::class)
            ->createQueryBuilder('p')
            ->where('p.contributor = :contributor')
            ->andWhere('p.startDate <= :end')
            ->andWhere('p.endDate >= :start')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('contributor', $contributor)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statuses', ['planned', 'confirmed'])
            ->getQuery()
            ->getResult();

        $totalPlannedDays = 0.0;

        foreach ($plannings as $planning) {
            /** @var \App\Entity\Planning $planning */
            // Calculer l'intersection entre la planification et la période
            $planStart = max($planning->getStartDate(), $start);
            $planEnd   = min($planning->getEndDate(), $end);

            // Compter les jours ouvrés dans cette intersection
            $workingDays = $this->calculateWorkingDays($planStart, $planEnd);

            // Calculer le nombre de jours planifiés selon les heures quotidiennes
            // Si dailyHours = 8, on compte 1 jour par jour ouvré
            // Si dailyHours = 4, on compte 0.5 jour par jour ouvré
            $dailyHours  = (float) $planning->getDailyHours();
            $plannedDays = $workingDays * ($dailyHours / 8.0);

            $totalPlannedDays += $plannedDays;
        }

        return $totalPlannedDays;
    }

    /**
     * Récupère ou crée une dimension temporelle.
     */
    private function getOrCreateDimTime(DateTimeInterface $date): DimTime
    {
        $dimTimeRepo = $this->entityManager->getRepository(DimTime::class);

        $dimTime = $dimTimeRepo->findOneBy(['date' => $date]);

        if (!$dimTime) {
            $dimTime = new DimTime();
            $dimTime->setDate($date);
            $this->entityManager->persist($dimTime);
        }

        return $dimTime;
    }

    /**
     * Récupère ou crée une dimension de profil.
     */
    private function getOrCreateDimProfile(EmploymentPeriod $employmentPeriod): ?DimProfile
    {
        $profiles = $employmentPeriod->getProfiles();

        if ($profiles->isEmpty()) {
            return null;
        }

        // Prendre le premier profil (on pourrait avoir une logique plus complexe)
        $profile = $profiles->first();

        $dimProfileRepo = $this->entityManager->getRepository(DimProfile::class);

        // Rechercher par composite key
        $compositeKey = sprintf(
            '%s_%s_productive_active',
            $profile->getId(),
            md5($profile->getName()),
        );

        $dimProfile = $dimProfileRepo->findOneBy(['compositeKey' => $compositeKey]);

        if (!$dimProfile) {
            $dimProfile = new DimProfile();
            $dimProfile->setProfile($profile);
            $dimProfile->setName($profile->getName());
            $dimProfile->setIsProductive(true); // Par défaut productif
            $dimProfile->setIsActive(true);
            $this->entityManager->persist($dimProfile);
            // Flush immédiatement pour éviter les doublons dans la même transaction
            $this->entityManager->flush();
        }

        return $dimProfile;
    }

    /**
     * Récupère la période d'emploi active pour un contributeur à une date donnée.
     */
    private function getActiveEmploymentPeriod(Contributor $contributor, DateTimeInterface $date): ?EmploymentPeriod
    {
        $employmentPeriods = $contributor->getEmploymentPeriods();

        foreach ($employmentPeriods as $period) {
            if ($period->isActiveAt($date)) {
                return $period;
            }
        }

        return null;
    }

    /**
     * Calcule la date de fin d'une période selon la granularité.
     */
    private function calculatePeriodEnd(DateTimeInterface $periodStart, string $granularity): DateTimeInterface
    {
        $end = clone $periodStart;

        switch ($granularity) {
            case 'monthly':
                // Dernier jour du mois
                $end->modify('last day of this month');
                break;

            case 'quarterly':
                // Dernier jour du trimestre (3 mois)
                $end->modify('+2 months');
                $end->modify('last day of this month');
                break;

            case 'weekly':
                // 6 jours après (dimanche si lundi)
                $end->modify('+6 days');
                break;

            default:
                throw new InvalidArgumentException("Granularity '$granularity' not supported");
        }

        return $end;
    }

    /**
     * Génère les périodes selon la granularité.
     *
     * @return DateTimeInterface[]
     */
    private function generatePeriods(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity
    ): array {
        $periods = [];

        switch ($granularity) {
            case 'monthly':
                $interval = new DateInterval('P1M');
                $current  = (new DateTime($startDate->format('Y-m-01')));
                $end      = (new DateTime($endDate->format('Y-m-01')));

                while ($current <= $end) {
                    $periods[] = clone $current;
                    $current->add($interval);
                }
                break;

            case 'quarterly':
                // Générer les trimestres
                $currentYear = (int) $startDate->format('Y');
                $endYear     = (int) $endDate->format('Y');

                for ($year = $currentYear; $year <= $endYear; ++$year) {
                    for ($quarter = 1; $quarter <= 4; ++$quarter) {
                        $month        = ($quarter - 1) * 3 + 1;
                        $quarterStart = new DateTime(sprintf('%d-%02d-01', $year, $month));

                        if ($quarterStart >= $startDate && $quarterStart <= $endDate) {
                            $periods[] = $quarterStart;
                        }
                    }
                }
                break;

            case 'weekly':
                $interval = new DateInterval('P1W');
                $current  = $startDate instanceof DateTime
                    ? clone $startDate
                    : new DateTime($startDate->format('Y-m-d'));

                $end = $endDate instanceof DateTime
                    ? clone $endDate
                    : new DateTime($endDate->format('Y-m-d'));

                while ($current <= $end) {
                    $periods[] = clone $current;
                    $current->add($interval);
                }
                break;

            default:
                throw new InvalidArgumentException("Granularity '$granularity' not supported");
        }

        return $periods;
    }
}

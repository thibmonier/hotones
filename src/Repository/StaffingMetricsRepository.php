<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Analytics\FactStaffingMetrics;
use App\Entity\Contributor;
use App\Entity\Profile;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StaffingMetricsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactStaffingMetrics::class);
    }

    /**
     * Récupère les métriques de staffing pour une période donnée.
     *
     * @param DateTimeInterface $startDate   Date de début
     * @param DateTimeInterface $endDate     Date de fin
     * @param string            $granularity Granularité (weekly, monthly, quarterly)
     * @param Profile|null      $profile     Filtre par profil (optionnel)
     * @param Contributor|null  $contributor Filtre par contributeur (optionnel)
     *
     * @return FactStaffingMetrics[]
     */
    public function findByPeriod(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'monthly',
        ?Profile $profile = null,
        ?Contributor $contributor = null
    ): array {
        $qb = $this->createQueryBuilder('fsm')
            ->join('fsm.dimTime', 'dt')
            ->where('dt.date >= :startDate')
            ->andWhere('dt.date <= :endDate')
            ->andWhere('fsm.granularity = :granularity')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('granularity', $granularity)
            ->orderBy('dt.date', 'ASC');

        if ($profile) {
            $qb->join('fsm.dimProfile', 'dp')
               ->andWhere('dp.profile = :profile')
               ->setParameter('profile', $profile);
        }

        if ($contributor) {
            $qb->andWhere('fsm.contributor = :contributor')
               ->setParameter('contributor', $contributor);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les métriques agrégées pour tous les profils productifs.
     *
     * @param DateTimeInterface $startDate   Date de début
     * @param DateTimeInterface $endDate     Date de fin
     * @param string            $granularity Granularité
     *
     * @return array<array{
     *     yearMonth: string,
     *     staffingRate: string,
     *     tace: string,
     *     contributorCount: int
     * }>
     */
    public function getAggregatedMetricsByPeriod(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'monthly',
        ?Profile $profile = null,
        ?Contributor $contributor = null
    ): array {
        $qb = $this->createQueryBuilder('fsm')
            ->select(
                'dt.yearMonth as yearMonth',
                'AVG(fsm.staffingRate) as staffingRate',
                'AVG(fsm.tace) as tace',
                'SUM(fsm.contributorCount) as contributorCount',
            )
            ->join('fsm.dimTime', 'dt')
            ->leftJoin('fsm.dimProfile', 'dp')
            ->where('dt.date >= :startDate')
            ->andWhere('dt.date <= :endDate')
            ->andWhere('fsm.granularity = :granularity')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('granularity', $granularity);

        // Ne filtrer par productivité que s'il y a un profil
        // Les métriques sans profil (dimProfile NULL) sont considérées comme productives par défaut
        if ($profile) {
            $qb->andWhere('dp.profile = :profile')
               ->setParameter('profile', $profile);
        } else {
            // Si pas de filtre profil spécifique, inclure soit les profils productifs soit pas de profil du tout
            $qb->andWhere('dp.id IS NULL OR dp.isProductive = true');
        }

        if ($contributor) {
            $qb->andWhere('fsm.contributor = :contributor')
               ->setParameter('contributor', $contributor);
        }

        $qb->groupBy('dt.yearMonth')
           ->orderBy('dt.yearMonth', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les métriques par profil pour une période donnée.
     *
     * @param DateTimeInterface $startDate   Date de début
     * @param DateTimeInterface $endDate     Date de fin
     * @param string            $granularity Granularité
     *
     * @return array<array{
     *     profileName: string,
     *     staffingRate: string,
     *     tace: string
     * }>
     */
    public function getMetricsByProfile(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'monthly',
        ?Profile $profile = null,
        ?Contributor $contributor = null
    ): array {
        $qb = $this->createQueryBuilder('fsm')
            ->select(
                'dp.name as profileName',
                'AVG(fsm.staffingRate) as staffingRate',
                'AVG(fsm.tace) as tace',
            )
            ->join('fsm.dimTime', 'dt')
            ->join('fsm.dimProfile', 'dp')
            ->where('dt.date >= :startDate')
            ->andWhere('dt.date <= :endDate')
            ->andWhere('fsm.granularity = :granularity')
            ->andWhere('dp.isProductive = true')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('granularity', $granularity);

        if ($profile) {
            $qb->andWhere('dp.profile = :profile')
               ->setParameter('profile', $profile);
        }

        if ($contributor) {
            $qb->andWhere('fsm.contributor = :contributor')
               ->setParameter('contributor', $contributor);
        }

        $qb->groupBy('dp.name')
           ->orderBy('staffingRate', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les métriques par contributeur pour une période donnée.
     *
     * @param DateTimeInterface $startDate   Date de début
     * @param DateTimeInterface $endDate     Date de fin
     * @param string            $granularity Granularité
     *
     * @return array<array{
     *     contributorId: int,
     *     contributorName: string,
     *     staffingRate: string,
     *     tace: string
     * }>
     */
    public function getMetricsByContributor(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'monthly',
        ?Profile $profile = null,
        ?Contributor $contributor = null
    ): array {
        $qb = $this->createQueryBuilder('fsm')
            ->select(
                'c.id as contributorId',
                'CONCAT(c.firstName, \' \' , c.lastName) as contributorName',
                'AVG(fsm.staffingRate) as staffingRate',
                'AVG(fsm.tace) as tace',
            )
            ->join('fsm.dimTime', 'dt')
            ->join('fsm.contributor', 'c')
            ->leftJoin('fsm.dimProfile', 'dp')
            ->where('dt.date >= :startDate')
            ->andWhere('dt.date <= :endDate')
            ->andWhere('fsm.granularity = :granularity')
            ->andWhere('fsm.contributor IS NOT NULL')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('granularity', $granularity);

        if ($profile) {
            $qb->andWhere('dp.profile = :profile')
               ->setParameter('profile', $profile);
        } else {
            // Inclure les contributeurs avec profils productifs ou sans profil
            $qb->andWhere('dp.id IS NULL OR dp.isProductive = true');
        }

        if ($contributor) {
            $qb->andWhere('fsm.contributor = :contributor')
               ->setParameter('contributor', $contributor);
        }

        $qb->groupBy('c.id', 'c.firstName', 'c.lastName')
           ->orderBy('staffingRate', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Supprime les anciennes métriques pour recalculer.
     *
     * @param DateTimeInterface $date        Période à supprimer
     * @param string            $granularity Granularité
     */
    public function deleteForPeriod(DateTimeInterface $date, string $granularity): void
    {
        $this->createQueryBuilder('fsm')
            ->delete()
            ->join('fsm.dimTime', 'dt')
            ->where('dt.date = :date')
            ->andWhere('fsm.granularity = :granularity')
            ->setParameter('date', $date)
            ->setParameter('granularity', $granularity)
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime les métriques pour une plage de dates.
     *
     * @param DateTimeInterface $startDate   Date de début
     * @param DateTimeInterface $endDate     Date de fin
     * @param string            $granularity Granularité
     */
    public function deleteForDateRange(DateTimeInterface $startDate, DateTimeInterface $endDate, string $granularity): int
    {
        // D'abord, récupérer les IDs des métriques à supprimer (DQL DELETE ne supporte pas les JOINs)
        $ids = $this->createQueryBuilder('fsm')
            ->select('fsm.id')
            ->join('fsm.dimTime', 'dt')
            ->where('dt.date >= :startDate')
            ->andWhere('dt.date <= :endDate')
            ->andWhere('fsm.granularity = :granularity')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('granularity', $granularity)
            ->getQuery()
            ->getResult();

        if (empty($ids)) {
            return 0;
        }

        // Extraire les IDs
        $idList = array_map(fn ($row) => $row['id'], $ids);

        // Supprimer par IDs
        $result = $this->createQueryBuilder('fsm')
            ->delete()
            ->where('fsm.id IN (:ids)')
            ->setParameter('ids', $idList)
            ->getQuery()
            ->execute();

        return (int) $result;
    }

    /**
     * Vérifie si des métriques existent pour une période donnée.
     *
     * @param DateTimeInterface $date        Période à vérifier
     * @param string            $granularity Granularité
     */
    public function existsForPeriod(DateTimeInterface $date, string $granularity): bool
    {
        $count = $this->createQueryBuilder('fsm')
            ->select('COUNT(fsm.id)')
            ->join('fsm.dimTime', 'dt')
            ->where('dt.date = :date')
            ->andWhere('fsm.granularity = :granularity')
            ->setParameter('date', $date)
            ->setParameter('granularity', $granularity)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Récupère les métriques hebdomadaires par contributeur pour une année complète.
     * Utile pour afficher le taux d'occupation par semaine pour chaque contributeur.
     *
     * @param int          $year    Année à analyser
     * @param Profile|null $profile Filtre par profil (optionnel)
     *
     * @return array<array{
     *     contributorId: int,
     *     contributorName: string,
     *     weekNumber: string,
     *     availableDays: string,
     *     staffedDays: string,
     *     plannedDays: string,
     *     vacationDays: string,
     *     occupancyRate: float,
     *     remainingCapacity: float
     * }>
     */
    public function getWeeklyOccupancyByContributor(int $year, ?Profile $profile = null): array
    {
        $qb = $this->createQueryBuilder('fsm')
            ->select(
                'c.id as contributorId',
                'CONCAT(c.firstName, \' \', c.lastName) as contributorName',
                'dt.date as weekDate',
                'fsm.availableDays',
                'fsm.staffedDays',
                'fsm.plannedDays',
                'fsm.vacationDays',
            )
            ->join('fsm.dimTime', 'dt')
            ->join('fsm.contributor', 'c')
            ->where('dt.year = :year')
            ->andWhere('fsm.granularity = :granularity')
            ->andWhere('fsm.contributor IS NOT NULL')
            ->setParameter('year', $year)
            ->setParameter('granularity', 'weekly')
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->addOrderBy('dt.date', 'ASC');

        if ($profile) {
            $qb->leftJoin('fsm.dimProfile', 'dp')
               ->andWhere('dp.profile = :profile')
               ->setParameter('profile', $profile);
        }

        $results = $qb->getQuery()->getResult();

        // Calculer le taux d'occupation et la capacité restante, formater le numéro de semaine
        foreach ($results as &$result) {
            // Calculer le numéro de semaine à partir de la date
            $date = $result['weekDate'];
            if ($date instanceof DateTimeInterface) {
                $weekNum = (int) $date->format('W');
            } else {
                $weekNum = 0;
            }

            // Formater le numéro de semaine comme 'YYYY-S##'
            $result['weekNumber'] = sprintf('%d-S%02d', $year, $weekNum);
            unset($result['weekDate']);

            $available = (float) $result['availableDays'];
            $staffed   = (float) $result['staffedDays'];
            $planned   = (float) $result['plannedDays'];

            // Taux d'occupation = (staffé + planifié) / disponible * 100
            $totalOccupied               = $staffed + $planned;
            $result['occupancyRate']     = $available > 0 ? ($totalOccupied / $available) * 100 : 0;
            $result['remainingCapacity'] = max(0, $available - $totalOccupied);
        }

        return $results;
    }

    /**
     * Récupère le TACE global (tous contributeurs productifs) par semaine pour une année.
     *
     * @param int          $year    Année à analyser
     * @param Profile|null $profile Filtre par profil (optionnel)
     *
     * @return array<array{
     *     weekNumber: string,
     *     tace: string,
     *     contributorCount: int,
     *     staffedDays: string,
     *     workedDays: string
     * }>
     */
    public function getWeeklyGlobalTACE(int $year, ?Profile $profile = null): array
    {
        $qb = $this->createQueryBuilder('fsm')
            ->select(
                'dt.date as weekDate',
                'AVG(fsm.tace) as tace',
                'SUM(fsm.contributorCount) as contributorCount',
                'SUM(fsm.staffedDays) as staffedDays',
                'SUM(fsm.workedDays) as workedDays',
            )
            ->join('fsm.dimTime', 'dt')
            ->leftJoin('fsm.dimProfile', 'dp')
            ->where('dt.year = :year')
            ->andWhere('fsm.granularity = :granularity')
            ->setParameter('year', $year)
            ->setParameter('granularity', 'weekly')
            ->groupBy('dt.date')
            ->orderBy('dt.date', 'ASC');

        if ($profile) {
            $qb->andWhere('dp.profile = :profile')
               ->setParameter('profile', $profile);
        } else {
            // Inclure les profils productifs ou sans profil
            $qb->andWhere('dp.id IS NULL OR dp.isProductive = true');
        }

        $results = $qb->getQuery()->getResult();

        // Formater le numéro de semaine comme 'YYYY-S##'
        foreach ($results as &$result) {
            // Calculer le numéro de semaine à partir de la date
            $date = $result['weekDate'];
            if ($date instanceof DateTimeInterface) {
                $weekNum = (int) $date->format('W');
            } else {
                $weekNum = 0;
            }

            $result['weekNumber'] = sprintf('%d-S%02d', $year, $weekNum);
            unset($result['weekDate']);
        }

        return $results;
    }
}

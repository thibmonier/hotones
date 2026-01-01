<?php

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\ContributorSatisfaction;
use App\Security\CompanyContext;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<ContributorSatisfaction>
 */
class ContributorSatisfactionRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, ContributorSatisfaction::class, $companyContext);
    }

    /**
     * Récupère la satisfaction d'un contributeur pour une période donnée.
     */
    public function findByContributorAndPeriod(Contributor $contributor, int $year, int $month): ?ContributorSatisfaction
    {
        return $this->findOneBy([
            'contributor' => $contributor,
            'year'        => $year,
            'month'       => $month,
        ]);
    }

    /**
     * Récupère toutes les satisfactions d'un contributeur, triées par période.
     */
    public function findByContributor(Contributor $contributor): array
    {
        return $this->createCompanyQueryBuilder('cs')
            ->where('cs.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('cs.year', 'DESC')
            ->addOrderBy('cs.month', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère toutes les satisfactions pour une période donnée.
     */
    public function findByPeriod(int $year, int $month): array
    {
        return $this->createCompanyQueryBuilder('cs')
            ->where('cs.year = :year')
            ->andWhere('cs.month = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère toutes les satisfactions pour une année donnée.
     */
    public function findByYear(int $year): array
    {
        return $this->createCompanyQueryBuilder('cs')
            ->where('cs.year = :year')
            ->setParameter('year', $year)
            ->orderBy('cs.month', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la moyenne des scores globaux pour une période donnée.
     */
    public function getAverageScoreByPeriod(int $year, int $month): ?float
    {
        $result = $this->createCompanyQueryBuilder('cs')
            ->select('AVG(cs.overallScore) as avg_score')
            ->where('cs.year = :year')
            ->andWhere('cs.month = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (float) $result : null;
    }

    /**
     * Calcule la moyenne des scores globaux pour une année donnée.
     */
    public function getAverageScoreByYear(int $year): ?float
    {
        $result = $this->createCompanyQueryBuilder('cs')
            ->select('AVG(cs.overallScore) as avg_score')
            ->where('cs.year = :year')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (float) $result : null;
    }

    /**
     * Récupère les statistiques détaillées pour une période.
     */
    public function getStatsByPeriod(int $year, int $month): array
    {
        $satisfactions = $this->findByPeriod($year, $month);

        if (empty($satisfactions)) {
            return [
                'total'                     => 0,
                'average_overall'           => null,
                'average_projects'          => null,
                'average_team'              => null,
                'average_work_environment'  => null,
                'average_work_life_balance' => null,
                'distribution'              => [],
            ];
        }

        $total        = count($satisfactions);
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        $sumOverall           = 0;
        $sumProjects          = 0;
        $countProjects        = 0;
        $sumTeam              = 0;
        $countTeam            = 0;
        $sumWorkEnvironment   = 0;
        $countWorkEnvironment = 0;
        $sumWorkLifeBalance   = 0;
        $countWorkLifeBalance = 0;

        foreach ($satisfactions as $satisfaction) {
            $score = $satisfaction->getOverallScore();
            $sumOverall += $score;
            ++$distribution[$score];

            if ($satisfaction->getProjectsScore() !== null) {
                $sumProjects += $satisfaction->getProjectsScore();
                ++$countProjects;
            }

            if ($satisfaction->getTeamScore() !== null) {
                $sumTeam += $satisfaction->getTeamScore();
                ++$countTeam;
            }

            if ($satisfaction->getWorkEnvironmentScore() !== null) {
                $sumWorkEnvironment += $satisfaction->getWorkEnvironmentScore();
                ++$countWorkEnvironment;
            }

            if ($satisfaction->getWorkLifeBalanceScore() !== null) {
                $sumWorkLifeBalance += $satisfaction->getWorkLifeBalanceScore();
                ++$countWorkLifeBalance;
            }
        }

        return [
            'total'                     => $total,
            'average_overall'           => round($sumOverall / $total, 2),
            'average_projects'          => $countProjects        > 0 ? round($sumProjects / $countProjects, 2) : null,
            'average_team'              => $countTeam            > 0 ? round($sumTeam / $countTeam, 2) : null,
            'average_work_environment'  => $countWorkEnvironment > 0 ? round($sumWorkEnvironment / $countWorkEnvironment, 2) : null,
            'average_work_life_balance' => $countWorkLifeBalance > 0 ? round($sumWorkLifeBalance / $countWorkLifeBalance, 2) : null,
            'distribution'              => $distribution,
        ];
    }

    /**
     * Récupère les statistiques détaillées pour une année.
     */
    public function getStatsByYear(int $year): array
    {
        $satisfactions = $this->findByYear($year);

        if (empty($satisfactions)) {
            return [
                'total'                     => 0,
                'average_overall'           => null,
                'average_projects'          => null,
                'average_team'              => null,
                'average_work_environment'  => null,
                'average_work_life_balance' => null,
                'monthly_averages'          => [],
            ];
        }

        $total       = count($satisfactions);
        $monthlyData = [];

        $sumOverall           = 0;
        $sumProjects          = 0;
        $countProjects        = 0;
        $sumTeam              = 0;
        $countTeam            = 0;
        $sumWorkEnvironment   = 0;
        $countWorkEnvironment = 0;
        $sumWorkLifeBalance   = 0;
        $countWorkLifeBalance = 0;

        foreach ($satisfactions as $satisfaction) {
            $month = $satisfaction->getMonth();

            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'count' => 0,
                    'sum'   => 0,
                ];
            }

            ++$monthlyData[$month]['count'];
            $monthlyData[$month]['sum'] += $satisfaction->getOverallScore();

            $sumOverall += $satisfaction->getOverallScore();

            if ($satisfaction->getProjectsScore() !== null) {
                $sumProjects += $satisfaction->getProjectsScore();
                ++$countProjects;
            }

            if ($satisfaction->getTeamScore() !== null) {
                $sumTeam += $satisfaction->getTeamScore();
                ++$countTeam;
            }

            if ($satisfaction->getWorkEnvironmentScore() !== null) {
                $sumWorkEnvironment += $satisfaction->getWorkEnvironmentScore();
                ++$countWorkEnvironment;
            }

            if ($satisfaction->getWorkLifeBalanceScore() !== null) {
                $sumWorkLifeBalance += $satisfaction->getWorkLifeBalanceScore();
                ++$countWorkLifeBalance;
            }
        }

        // Calculer les moyennes mensuelles
        $monthlyAverages = [];
        for ($month = 1; $month <= 12; ++$month) {
            if (isset($monthlyData[$month])) {
                $monthlyAverages[$month] = round($monthlyData[$month]['sum'] / $monthlyData[$month]['count'], 2);
            } else {
                $monthlyAverages[$month] = null;
            }
        }

        return [
            'total'                     => $total,
            'average_overall'           => round($sumOverall / $total, 2),
            'average_projects'          => $countProjects        > 0 ? round($sumProjects / $countProjects, 2) : null,
            'average_team'              => $countTeam            > 0 ? round($sumTeam / $countTeam, 2) : null,
            'average_work_environment'  => $countWorkEnvironment > 0 ? round($sumWorkEnvironment / $countWorkEnvironment, 2) : null,
            'average_work_life_balance' => $countWorkLifeBalance > 0 ? round($sumWorkLifeBalance / $countWorkLifeBalance, 2) : null,
            'monthly_averages'          => $monthlyAverages,
        ];
    }

    /**
     * Récupère l'évolution des scores d'un contributeur sur les N derniers mois.
     */
    public function getContributorTrend(Contributor $contributor, int $months = 12): array
    {
        return $this->createCompanyQueryBuilder('cs')
            ->where('cs.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->orderBy('cs.year', 'DESC')
            ->addOrderBy('cs.month', 'DESC')
            ->setMaxResults($months)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de contributeurs qui ont saisi leur satisfaction pour une période.
     */
    public function countByPeriod(int $year, int $month): int
    {
        return (int) $this->createCompanyQueryBuilder('cs')
            ->select('COUNT(DISTINCT cs.contributor)')
            ->where('cs.year = :year')
            ->andWhere('cs.month = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère l'évolution de la satisfaction pour les contributeurs d'un projet.
     * Retourne les moyennes mensuelles des scores de satisfaction des contributeurs
     * ayant travaillé sur le projet (basé sur les timesheets).
     *
     * @param int $projectId ID du projet
     * @param int $months    Nombre de mois à récupérer (par défaut 12)
     *
     * @return array Format: [['period' => 'YYYY-MM', 'avgOverall' => float, 'avgProjects' => float, ...], ...]
     */
    public function getProjectSatisfactionEvolution(int $projectId, int $months = 12): array
    {
        $endDate   = new DateTime();
        $startDate = (clone $endDate)->modify("-{$months} months");

        // Récupérer les contributeurs ayant travaillé sur le projet
        $contributorIds = $this->createCompanyQueryBuilder('cs')
            ->select('DISTINCT IDENTITY(t.contributor)')
            ->from(\App\Entity\Timesheet::class, 't')
            ->where('t.project = :projectId')
            ->andWhere('t.date BETWEEN :startDate AND :endDate')
            ->setParameter('projectId', $projectId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleColumnResult();

        if (empty($contributorIds)) {
            return [];
        }

        // Récupérer les satisfactions de ces contributeurs pour la période
        $qb = $this->createCompanyQueryBuilder('cs');
        $qb->where($qb->expr()->in('cs.contributor', ':contributorIds'))
            ->setParameter('contributorIds', $contributorIds)
            ->orderBy('cs.year', 'ASC')
            ->addOrderBy('cs.month', 'ASC');

        // Filtrer par période si nécessaire
        $qb->andWhere('(cs.year > :startYear) OR (cs.year = :startYear AND cs.month >= :startMonth)')
            ->andWhere('(cs.year < :endYear) OR (cs.year = :endYear AND cs.month <= :endMonth)')
            ->setParameter('startYear', (int) $startDate->format('Y'))
            ->setParameter('startMonth', (int) $startDate->format('n'))
            ->setParameter('endYear', (int) $endDate->format('Y'))
            ->setParameter('endMonth', (int) $endDate->format('n'));

        $satisfactions = $qb->getQuery()->getResult();

        // Grouper par période et calculer moyennes
        $grouped = [];
        foreach ($satisfactions as $satisfaction) {
            $period = sprintf('%d-%02d', $satisfaction->getYear(), $satisfaction->getMonth());

            if (!isset($grouped[$period])) {
                $grouped[$period] = [
                    'period'               => $period,
                    'responseCount'        => 0,
                    'sumOverall'           => 0,
                    'sumProjects'          => 0,
                    'countProjects'        => 0,
                    'sumTeam'              => 0,
                    'countTeam'            => 0,
                    'sumWorkEnvironment'   => 0,
                    'countWorkEnvironment' => 0,
                    'sumWorkLifeBalance'   => 0,
                    'countWorkLifeBalance' => 0,
                ];
            }

            ++$grouped[$period]['responseCount'];
            $grouped[$period]['sumOverall'] += $satisfaction->getOverallScore();

            if ($satisfaction->getProjectsScore() !== null) {
                $grouped[$period]['sumProjects'] += $satisfaction->getProjectsScore();
                ++$grouped[$period]['countProjects'];
            }

            if ($satisfaction->getTeamScore() !== null) {
                $grouped[$period]['sumTeam'] += $satisfaction->getTeamScore();
                ++$grouped[$period]['countTeam'];
            }

            if ($satisfaction->getWorkEnvironmentScore() !== null) {
                $grouped[$period]['sumWorkEnvironment'] += $satisfaction->getWorkEnvironmentScore();
                ++$grouped[$period]['countWorkEnvironment'];
            }

            if ($satisfaction->getWorkLifeBalanceScore() !== null) {
                $grouped[$period]['sumWorkLifeBalance'] += $satisfaction->getWorkLifeBalanceScore();
                ++$grouped[$period]['countWorkLifeBalance'];
            }
        }

        // Calculer les moyennes
        $result = [];
        foreach ($grouped as $period => $data) {
            $count    = $data['responseCount'];
            $result[] = [
                'period'             => $period,
                'responseCount'      => $count,
                'avgOverall'         => round($data['sumOverall'] / $count, 2),
                'avgProjects'        => $data['countProjects']        > 0 ? round($data['sumProjects'] / $data['countProjects'], 2) : null,
                'avgTeam'            => $data['countTeam']            > 0 ? round($data['sumTeam'] / $data['countTeam'], 2) : null,
                'avgWorkEnvironment' => $data['countWorkEnvironment'] > 0 ? round($data['sumWorkEnvironment'] / $data['countWorkEnvironment'], 2) : null,
                'avgWorkLifeBalance' => $data['countWorkLifeBalance'] > 0 ? round($data['sumWorkLifeBalance'] / $data['countWorkLifeBalance'], 2) : null,
            ];
        }

        return $result;
    }
}

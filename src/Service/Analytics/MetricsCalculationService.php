<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\Entity\Analytics\DimContributor;
use App\Entity\Analytics\DimProjectType;
use App\Entity\Analytics\DimTime;
use App\Entity\Analytics\FactProjectMetrics;
use App\Entity\Order;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Service de calcul et mise à jour des métriques dans le modèle en étoile.
 */
readonly class MetricsCalculationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Calcule et met à jour toutes les métriques pour une période donnée.
     *
     * @throws Exception
     */
    public function calculateMetricsForPeriod(DateTimeInterface $date, string $granularity = 'monthly'): void
    {
        $this->logger->info('Début du calcul des métriques', [
            'date'        => $date->format('Y-m-d'),
            'granularity' => $granularity,
        ]);

        try {
            // 1. Préparer les dimensions temporelles
            $dimTime = $this->getOrCreateDimTime($date);

            // 2. Récupérer tous les projets pour la période
            $projects = $this->getProjectsForPeriod($date, $granularity);

            // 3. Calculer les métriques par dimension
            foreach ($projects as $project) {
                $this->calculateProjectMetrics($project, $dimTime, $granularity);
            }

            $this->entityManager->flush();
            $this->logger->info('Calcul des métriques terminé avec succès');
        } catch (Exception $e) {
            $this->logger->error('Erreur lors du calcul des métriques', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Calcule les métriques pour un projet spécifique.
     */
    private function calculateProjectMetrics(Project $project, DimTime $dimTime, string $granularity): void
    {
        // Obtenir ou créer les dimensions
        $dimProjectType     = $this->getOrCreateDimProjectType($project);
        $dimProjectManager  = $this->getOrCreateDimContributor($project->getProjectManager(), 'project_manager');
        $dimSalesPerson     = $this->getOrCreateDimContributor($project->getSalesPerson(), 'sales_person');
        $dimProjectDirector = $this->getOrCreateDimContributor($project->getProjectDirector(), 'project_director');

        // Calculer les métriques pour chaque devis du projet
        foreach ($project->getOrders() as $order) {
            $metrics = $this->getOrCreateFactMetrics(
                $dimTime,
                $dimProjectType,
                $dimProjectManager,
                $dimSalesPerson,
                $dimProjectDirector,
                $granularity,
            );

            $this->updateMetricsFromProject($metrics, $project, $order);
        }

        // Si le projet n'a pas de devis, créer quand même une métrique
        if ($project->getOrders()->isEmpty()) {
            $metrics = $this->getOrCreateFactMetrics(
                $dimTime,
                $dimProjectType,
                $dimProjectManager,
                $dimSalesPerson,
                $dimProjectDirector,
                $granularity,
            );
            $this->updateMetricsFromProject($metrics, $project, null);
        }
    }

    /**
     * Met à jour les métriques à partir d'un projet et optionnellement d'un devis.
     */
    private function updateMetricsFromProject(FactProjectMetrics $metrics, Project $project, ?Order $order): void
    {
        // Métriques de base
        $metrics->setProjectCount($metrics->getProjectCount() + 1);

        if ($project->getStatus() === 'active') {
            $metrics->setActiveProjectCount($metrics->getActiveProjectCount() + 1);
        } elseif ($project->getStatus() === 'completed') {
            $metrics->setCompletedProjectCount($metrics->getCompletedProjectCount() + 1);
        }

        if ($order) {
            // Métriques de devis
            $metrics->setOrderCount($metrics->getOrderCount() + 1);

            switch ($order->getStatus()) {
                case 'a_signer':
                    $metrics->setPendingOrderCount($metrics->getPendingOrderCount() + 1);
                    $metrics->setPendingRevenue(
                        bcadd($metrics->getPendingRevenue(), $order->getTotalAmount(), 2),
                    );
                    break;
                case 'gagne':
                case 'signe':
                    $metrics->setWonOrderCount($metrics->getWonOrderCount() + 1);
                    $metrics->setTotalRevenue(
                        bcadd($metrics->getTotalRevenue(), $order->getTotalAmount(), 2),
                    );
                    break;
            }

            // Calcul de la valeur moyenne des devis
            if ($metrics->getOrderCount() > 0) {
                $totalOrderValue = bcadd($metrics->getTotalRevenue(), $metrics->getPendingRevenue(), 2);
                $metrics->setAverageOrderValue(
                    bcdiv($totalOrderValue, (string) $metrics->getOrderCount(), 2),
                );
            }
        }

        // Calcul des coûts et marges
        $this->calculateProjectCosts($metrics, $project);
        $metrics->calculateMargins();

        // Calcul des temps et taux d'occupation
        $this->calculateTimeMetrics($metrics, $project);

        // Mise à jour de la référence vers les entités sources
        $metrics->setProject($project);
        if ($order) {
            $metrics->setOrder($order);
        }

        $metrics->setCalculatedAt(new DateTime());
    }

    /**
     * Calcule les coûts d'un projet.
     */
    private function calculateProjectCosts(FactProjectMetrics $metrics, Project $project): void
    {
        $totalCosts = '0.00';

        // Coûts des achats sur le projet
        if ($project->getPurchasesAmount()) {
            $totalCosts = bcadd($totalCosts, $project->getPurchasesAmount(), 2);
        }

        // Coûts des temps passés (CJM * jours travaillés)
        $timesheets = $this->entityManager->getRepository(Timesheet::class)
            ->createQueryBuilder('t')
            ->join('t.project', 'p')
            ->where('p.id = :projectId')
            ->setParameter('projectId', $project->getId())
            ->getQuery()
            ->getResult();

        foreach ($timesheets as $timesheet) {
            $contributor = $timesheet->getContributor();
            if ($contributor && $contributor->getCjm()) {
                $dailyCost  = bcdiv($contributor->getCjm(), '8', 4); // Coût horaire
                $timeCost   = bcmul($dailyCost, (string) $timesheet->getHours(), 2);
                $totalCosts = bcadd($totalCosts, $timeCost, 2);
            }
        }

        $metrics->setTotalCosts($totalCosts);
    }

    /**
     * Calcule les métriques de temps.
     */
    private function calculateTimeMetrics(FactProjectMetrics $metrics, Project $project): void
    {
        // Jours vendus
        $soldDays = $project->getTotalSoldDays();
        $metrics->setTotalSoldDays(bcadd($metrics->getTotalSoldDays(), $soldDays, 2));

        // Jours travaillés réels
        $workedDays = '0.00';
        $timesheets = $this->entityManager->getRepository(Timesheet::class)
            ->createQueryBuilder('t')
            ->join('t.project', 'p')
            ->where('p.id = :projectId')
            ->setParameter('projectId', $project->getId())
            ->getQuery()
            ->getResult();

        foreach ($timesheets as $timesheet) {
            $dailyHours = bcdiv((string) $timesheet->getHours(), '8', 2);
            $workedDays = bcadd($workedDays, $dailyHours, 2);
        }

        $metrics->setTotalWorkedDays(bcadd($metrics->getTotalWorkedDays(), $workedDays, 2));

        // Taux d'occupation
        if (bccomp($metrics->getTotalSoldDays(), '0', 2) > 0) {
            $utilizationRate = bcmul(
                bcdiv($metrics->getTotalWorkedDays(), $metrics->getTotalSoldDays(), 4),
                '100',
                2,
            );
            $metrics->setUtilizationRate($utilizationRate);
        }
    }

    /**
     * Récupère ou crée une dimension temporelle.
     */
    private function getOrCreateDimTime(DateTimeInterface $date): DimTime
    {
        $repo    = $this->entityManager->getRepository(DimTime::class);
        $dimTime = $repo->findOneBy(['date' => $date]);

        if (!$dimTime) {
            $dimTime = new DimTime();
            $dimTime->setDate($date);
            $this->entityManager->persist($dimTime);
            // Flush to ensure identifier is generated before usage in queries
            $this->entityManager->flush();
        }

        return $dimTime;
    }

    /**
     * Récupère ou crée une dimension type de projet.
     */
    private function getOrCreateDimProjectType(Project $project): DimProjectType
    {
        $serviceCategory = $project->getServiceCategory()?->getName();

        $compositeKey = sprintf(
            '%s_%s_%s_%s',
            $project->getProjectType(),
            $serviceCategory ?? 'null',
            $project->getStatus(),
            $project->getIsInternal() ? 'internal' : 'external',
        );

        $repo           = $this->entityManager->getRepository(DimProjectType::class);
        $dimProjectType = $repo->findOneBy(['compositeKey' => $compositeKey]);

        if (!$dimProjectType) {
            $dimProjectType = new DimProjectType();
            $dimProjectType->setProjectType($project->getProjectType())
                ->setServiceCategory($serviceCategory)
                ->setStatus($project->getStatus())
                ->setIsInternal($project->getIsInternal());
            $this->entityManager->persist($dimProjectType);
            $this->entityManager->flush();
        }

        return $dimProjectType;
    }

    /**
     * Récupère ou crée une dimension contributeur.
     */
    private function getOrCreateDimContributor(?User $user, string $role): ?DimContributor
    {
        if (!$user) {
            return null;
        }

        $repo           = $this->entityManager->getRepository(DimContributor::class);
        $dimContributor = $repo->findOneBy(['user' => $user, 'role' => $role, 'isActive' => true]);

        if (!$dimContributor) {
            $dimContributor = new DimContributor();
            $dimContributor->setUser($user)
                ->setName($user->getFullName() ?? $user->getEmail())
                ->setRole($role)
                ->setIsActive(true);
            $this->entityManager->persist($dimContributor);
            $this->entityManager->flush();
        }

        return $dimContributor;
    }

    /**
     * Récupère ou crée une métrique factuelle.
     */
    private function getOrCreateFactMetrics(
        DimTime $dimTime,
        DimProjectType $dimProjectType,
        ?DimContributor $dimProjectManager,
        ?DimContributor $dimSalesPerson,
        ?DimContributor $dimProjectDirector,
        string $granularity
    ): FactProjectMetrics {
        $repo = $this->entityManager->getRepository(FactProjectMetrics::class);

        $qb = $repo->createQueryBuilder('f')
            ->where('f.dimTime = :dimTime')
            ->andWhere('f.dimProjectType = :dimProjectType')
            ->andWhere('f.granularity = :granularity')
            ->setParameter('dimTime', $dimTime)
            ->setParameter('dimProjectType', $dimProjectType)
            ->setParameter('granularity', $granularity);

        if ($dimProjectManager) {
            $qb->andWhere('f.dimProjectManager = :dimProjectManager')
               ->setParameter('dimProjectManager', $dimProjectManager);
        } else {
            $qb->andWhere('f.dimProjectManager IS NULL');
        }

        if ($dimSalesPerson) {
            $qb->andWhere('f.dimSalesPerson = :dimSalesPerson')
               ->setParameter('dimSalesPerson', $dimSalesPerson);
        } else {
            $qb->andWhere('f.dimSalesPerson IS NULL');
        }

        if ($dimProjectDirector) {
            $qb->andWhere('f.dimProjectDirector = :dimProjectDirector')
               ->setParameter('dimProjectDirector', $dimProjectDirector);
        } else {
            $qb->andWhere('f.dimProjectDirector IS NULL');
        }

        $metrics = $qb->getQuery()->getOneOrNullResult();

        if (!$metrics) {
            $metrics = new FactProjectMetrics();
            $metrics->setDimTime($dimTime)
                ->setDimProjectType($dimProjectType)
                ->setDimProjectManager($dimProjectManager)
                ->setDimSalesPerson($dimSalesPerson)
                ->setDimProjectDirector($dimProjectDirector)
                ->setGranularity($granularity);
            $this->entityManager->persist($metrics);
        }

        return $metrics;
    }

    /**
     * Récupère les projets pour une période donnée.
     */
    private function getProjectsForPeriod(DateTimeInterface $date, string $granularity): array
    {
        $repo = $this->entityManager->getRepository(Project::class);
        $qb   = $repo->createQueryBuilder('p');

        switch ($granularity) {
            case 'monthly':
                $startDate = (clone $date)->modify('first day of this month');
                $endDate   = (clone $date)->modify('last day of this month');
                break;
            case 'quarterly':
                $quarter    = ceil((int) $date->format('n') / 3);
                $startMonth = ($quarter - 1) * 3 + 1;
                $startDate  = (new DateTime($date->format('Y').'-'.$startMonth.'-01'));
                $endDate    = (clone $startDate)->modify('+2 months')->modify('last day of this month');
                break;
            case 'yearly':
                $startDate = new DateTime($date->format('Y').'-01-01');
                $endDate   = new DateTime($date->format('Y').'-12-31');
                break;
            default:
                throw new InvalidArgumentException("Granularité non supportée: {$granularity}");
        }

        return $qb->where('p.startDate <= :endDate')
            ->andWhere('p.endDate >= :startDate OR p.endDate IS NULL')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recalcule toutes les métriques pour une année donnée.
     *
     * @throws Exception
     */
    public function recalculateMetricsForYear(int $year): void
    {
        $this->logger->info('Recalcul des métriques pour l\'année', ['year' => $year]);

        // Supprimer les métriques existantes pour cette année
        $this->entityManager->createQuery('
            DELETE FROM App\Entity\Analytics\FactProjectMetrics f
            WHERE f.dimTime IN (
                SELECT t.id FROM App\Entity\Analytics\DimTime t
                WHERE t.year = :year
            )
        ')->setParameter('year', $year)->execute();

        // Recalculer mois par mois
        for ($month = 1; $month <= 12; ++$month) {
            $date = new DateTime("$year-$month-01");
            $this->calculateMetricsForPeriod($date, 'monthly');
        }

        // Recalculer par trimestre
        for ($quarter = 1; $quarter <= 4; ++$quarter) {
            $month = ($quarter - 1) * 3 + 1;
            $date  = new DateTime("$year-$month-01");
            $this->calculateMetricsForPeriod($date, 'quarterly');
        }

        // Recalculer annuel
        $date = new DateTime("$year-01-01");
        $this->calculateMetricsForPeriod($date, 'yearly');
    }
}

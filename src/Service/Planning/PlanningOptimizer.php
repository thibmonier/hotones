<?php

declare(strict_types=1);

namespace App\Service\Planning;

use App\Entity\Contributor;
use App\Entity\Planning as PlanningEntity;
use App\Repository\ProjectRepository;

use function count;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Génère des recommandations d'optimisation du planning basées sur le TACE.
 */
class PlanningOptimizer
{
    public function __construct(
        private readonly TaceAnalyzer $taceAnalyzer,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectRepository $projectRepository
    ) {
    }

    /**
     * Génère des recommandations d'optimisation pour une période donnée.
     */
    public function generateRecommendations(?DateTime $startDate = null, ?DateTime $endDate = null): array
    {
        if (!$startDate) {
            $startDate = new DateTime('first day of this month');
        }
        if (!$endDate) {
            $endDate = new DateTime('last day of next month'); // 2 mois pour avoir de la visibilité
        }

        $analysis = $this->taceAnalyzer->analyzeAllContributors($startDate, $endDate);

        $recommendations = [];

        // Recommandations pour les surcharges critiques
        foreach ($analysis['critical'] as $item) {
            if ($item['status'] === 'critical_high') {
                $recommendations = array_merge(
                    $recommendations,
                    $this->generateOverloadRecommendations($item, $analysis, $startDate, $endDate),
                );
            } elseif ($item['status'] === 'critical_low') {
                $recommendations = array_merge(
                    $recommendations,
                    $this->generateUnderutilizationRecommendations($item, $analysis, $startDate, $endDate),
                );
            }
        }

        // Recommandations pour les surcharges non critiques
        foreach ($analysis['overloaded'] as $item) {
            $recommendations = array_merge(
                $recommendations,
                $this->generateOverloadRecommendations($item, $analysis, $startDate, $endDate),
            );
        }

        // Recommandations pour les sous-utilisations
        foreach ($analysis['underutilized'] as $item) {
            $recommendations = array_merge(
                $recommendations,
                $this->generateUnderutilizationRecommendations($item, $analysis, $startDate, $endDate),
            );
        }

        // Trier par priorité (score)
        usort($recommendations, fn ($a, $b) => $b['priority_score'] <=> $a['priority_score']);

        return [
            'recommendations' => $recommendations,
            'analysis'        => $analysis,
            'period'          => [
                'start' => $startDate,
                'end'   => $endDate,
            ],
            'summary' => $this->generateSummary($analysis, $recommendations),
        ];
    }

    /**
     * Génère des recommandations pour un contributeur surchargé.
     */
    private function generateOverloadRecommendations(array $item, array $fullAnalysis, DateTime $start, DateTime $end): array
    {
        /** @var Contributor $contributor */
        $contributor = $item['contributor'];
        $tace        = $item['tace'];
        $severity    = $item['severity'];

        $recommendations = [];

        // Récupérer les plannings actuels du contributeur
        $planningRepo = $this->entityManager->getRepository(PlanningEntity::class);
        $plannings    = $planningRepo->createQueryBuilder('p')
            ->where('p.contributor = :contributor')
            ->andWhere('p.startDate <= :end')
            ->andWhere('p.endDate >= :start')
            ->setParameter('contributor', $contributor)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        if (empty($plannings)) {
            // Pas de planning = recommandation générique
            $recommendations[] = [
                'type'            => 'reduce_workload',
                'contributor'     => $contributor,
                'title'           => sprintf('%s est surchargé (%d%% de TACE)', $contributor->getFullName(), $tace),
                'description'     => 'Aucun planning trouvé pour cette période. Vérifiez la charge de travail réelle.',
                'priority_score'  => $severity,
                'severity_level'  => $item['status'] === 'critical_high' ? 'critical' : 'high',
                'actions'         => [],
                'expected_impact' => null,
            ];

            return $recommendations;
        }

        // Analyser les plannings pour identifier les projets à décharger
        $projectsWorkload = [];
        foreach ($plannings as $planning) {
            $project = $planning->getProject();
            if (!$project) {
                continue;
            }

            $projectId = $project->getId();
            if (!isset($projectsWorkload[$projectId])) {
                $projectsWorkload[$projectId] = [
                    'project'      => $project,
                    'total_hours'  => 0,
                    'plannings'    => [],
                    'client_level' => $project->getClient()?->getServiceLevel(),
                ];
            }

            $projectsWorkload[$projectId]['total_hours'] += $this->calculatePlanningHours($planning);
            $projectsWorkload[$projectId]['plannings'][] = $planning;
        }

        // Trier par heures décroissantes et niveau de service client
        usort($projectsWorkload, function ($a, $b) {
            // Prioriser les clients à faible priorité
            $levelPriority = ['low' => 1, 'standard' => 2, 'priority' => 3, 'vip' => 4];
            $aPrio         = $levelPriority[$a['client_level'] ?? 'standard'] ?? 2;
            $bPrio         = $levelPriority[$b['client_level'] ?? 'standard'] ?? 2;

            if ($aPrio !== $bPrio) {
                return $aPrio <=> $bPrio; // Commencer par les clients basse priorité
            }

            return $b['total_hours'] <=> $a['total_hours']; // Puis par heures
        });

        // Trouver des contributeurs sous-utilisés avec les mêmes profils
        $underutilized          = array_merge($fullAnalysis['underutilized'], $fullAnalysis['optimal']);
        $compatibleContributors = $this->findCompatibleContributors($contributor, $underutilized);

        // Générer des recommandations de réaffectation
        $targetReduction = abs($item['deviation']); // Réduire de X points de %
        foreach ($projectsWorkload as $pw) {
            if (empty($compatibleContributors)) {
                break;
            }

            $project          = $pw['project'];
            $clientLevel      = $pw['client_level'] ?? 'standard';
            $clientLevelLabel = match ($clientLevel) {
                'vip'      => 'VIP',
                'priority' => 'Prioritaire',
                'standard' => 'Standard',
                'low'      => 'Basse priorité',
                default    => 'Non défini',
            };

            foreach ($compatibleContributors as $target) {
                $targetContributor = $target['contributor'];

                $recommendations[] = [
                    'type'        => 'reassign_planning',
                    'contributor' => $contributor,
                    'target'      => $targetContributor,
                    'project'     => $project,
                    'title'       => sprintf(
                        'Réaffecter %s de %s vers %s',
                        $project->getName(),
                        $contributor->getFullName(),
                        $targetContributor->getFullName(),
                    ),
                    'description' => sprintf(
                        'Transférer une partie du travail sur le projet "%s" (Client %s - Niveau: %s) vers %s (TACE actuel: %d%%). '
                        .'Cela permettra de réduire la charge de %s (TACE: %d%%).',
                        $project->getName(),
                        $project->getClient()?->getName() ?? 'N/A',
                        $clientLevelLabel,
                        $targetContributor->getFullName(),
                        $target['tace'],
                        $contributor->getFullName(),
                        $tace,
                    ),
                    'priority_score' => $severity + ($clientLevel === 'low' ? 20 : 0),
                    'severity_level' => $item['status'] === 'critical_high' ? 'critical' : 'high',
                    'actions'        => [
                        sprintf('Réduire l\'allocation de %s sur %s', $contributor->getFullName(), $project->getName()),
                        sprintf('Augmenter l\'allocation de %s sur %s', $targetContributor->getFullName(), $project->getName()),
                    ],
                    'expected_impact' => sprintf('Réduction estimée du TACE de %s : %.1f points', $contributor->getFullName(), $targetReduction / count($projectsWorkload)),
                    'client_priority' => $clientLevel,
                ];

                // Ne générer qu'une recommandation par projet
                break;
            }
        }

        return $recommendations;
    }

    /**
     * Génère des recommandations pour un contributeur sous-utilisé.
     */
    private function generateUnderutilizationRecommendations(array $item, array $fullAnalysis, DateTime $start, DateTime $end): array
    {
        /** @var Contributor $contributor */
        $contributor = $item['contributor'];
        $tace        = $item['tace'];
        $severity    = $item['severity'];

        $recommendations = [];

        // Trouver des projets en cours qui pourraient bénéficier de ressources supplémentaires
        $activeProjects = $this->projectRepository->findActiveOrderedByName();

        // Filtrer les projets où le contributeur a les compétences
        $compatibleProjects = [];
        foreach ($activeProjects as $project) {
            if ($this->isContributorCompatibleWithProject($contributor, $project)) {
                $compatibleProjects[] = $project;
            }
        }

        if (!empty($compatibleProjects)) {
            // Prioriser les projets clients VIP/Prioritaires
            usort($compatibleProjects, function ($a, $b) {
                $levelPriority = ['vip' => 4, 'priority' => 3, 'standard' => 2, 'low' => 1];
                $aPrio         = $levelPriority[$a->getClient()?->getServiceLevel() ?? 'standard'] ?? 2;
                $bPrio         = $levelPriority[$b->getClient()?->getServiceLevel() ?? 'standard'] ?? 2;

                return $bPrio <=> $aPrio;
            });

            foreach (array_slice($compatibleProjects, 0, 3) as $project) {
                $clientLevel      = $project->getClient()?->getServiceLevel() ?? 'standard';
                $clientLevelLabel = match ($clientLevel) {
                    'vip'      => 'VIP',
                    'priority' => 'Prioritaire',
                    'standard' => 'Standard',
                    'low'      => 'Basse priorité',
                    default    => 'Non défini',
                };

                $recommendations[] = [
                    'type'        => 'increase_allocation',
                    'contributor' => $contributor,
                    'project'     => $project,
                    'title'       => sprintf(
                        'Augmenter l\'allocation de %s sur %s',
                        $contributor->getFullName(),
                        $project->getName(),
                    ),
                    'description' => sprintf(
                        '%s est sous-utilisé (TACE: %d%%). Allouer des ressources sur le projet "%s" (Client: %s - Niveau: %s) '
                        .'permettrait d\'optimiser la charge et potentiellement d\'accélérer la livraison.',
                        $contributor->getFullName(),
                        $tace,
                        $project->getName(),
                        $project->getClient()?->getName() ?? 'N/A',
                        $clientLevelLabel,
                    ),
                    'priority_score' => $severity + ($clientLevel === 'vip' ? 30 : ($clientLevel === 'priority' ? 20 : 0)),
                    'severity_level' => $item['status'] === 'critical_low' ? 'critical' : 'medium',
                    'actions'        => [
                        sprintf('Créer un planning pour %s sur %s', $contributor->getFullName(), $project->getName()),
                    ],
                    'expected_impact' => sprintf('Augmentation estimée du TACE : %.1f points', abs($item['deviation']) / 3),
                    'client_priority' => $clientLevel,
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Trouve des contributeurs compatibles (mêmes profils) avec un contributeur donné.
     */
    private function findCompatibleContributors(Contributor $source, array $candidates): array
    {
        $sourceProfiles = $source->getProfiles()->toArray();
        $compatible     = [];

        foreach ($candidates as $candidate) {
            /** @var Contributor $targetContributor */
            $targetContributor = $candidate['contributor'];

            if ($targetContributor->getId() === $source->getId()) {
                continue;
            }

            // Vérifier les profils communs
            $targetProfiles = $targetContributor->getProfiles()->toArray();
            $commonProfiles = array_intersect($sourceProfiles, $targetProfiles);

            if (count($commonProfiles) > 0) {
                $compatible[] = $candidate;
            }
        }

        // Trier par TACE croissant (les plus sous-utilisés en premier)
        usort($compatible, fn ($a, $b) => $a['tace'] <=> $b['tace']);

        return $compatible;
    }

    /**
     * Vérifie si un contributeur est compatible avec un projet (basé sur les profils).
     */
    private function isContributorCompatibleWithProject(Contributor $contributor, $project): bool
    {
        // Pour l'instant, simple : vérifier si le contributeur a au moins un profil
        // Une version plus sophistiquée comparerait les profils requis par le projet
        return $contributor->getProfiles()->count() > 0;
    }

    /**
     * Calcule les heures d'un planning.
     */
    private function calculatePlanningHours(PlanningEntity $planning): float
    {
        $start = $planning->getStartDate();
        $end   = $planning->getEndDate();

        if (!$start || !$end) {
            return 0;
        }

        $days = $start->diff($end)->days + 1;

        // Estimer ~7h par jour en moyenne
        return $days * 7;
    }

    /**
     * Génère un résumé des recommandations.
     */
    private function generateSummary(array $analysis, array $recommendations): array
    {
        return [
            'total_contributors'    => count($analysis['critical']) + count($analysis['overloaded']) + count($analysis['underutilized']) + count($analysis['optimal']),
            'critical_count'        => count($analysis['critical']),
            'overloaded_count'      => count($analysis['overloaded']),
            'underutilized_count'   => count($analysis['underutilized']),
            'optimal_count'         => count($analysis['optimal']),
            'total_recommendations' => count($recommendations),
            'high_priority_count'   => count(array_filter($recommendations, fn ($r) => $r['severity_level'] === 'critical' || $r['severity_level'] === 'high')),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Service\Planning;

use App\Entity\Contributor;
use App\Entity\Planning as PlanningEntity;
use App\Repository\ProjectRepository;
use App\Security\CompanyContext;

use function count;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Génère des recommandations d'optimisation du planning basées sur le TACE.
 */
class PlanningOptimizer
{
    public function __construct(
        private readonly TaceAnalyzer $taceAnalyzer,
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyContext $companyContext,
        private readonly ProjectRepository $projectRepository,
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
                $recommendations = array_merge($recommendations, $this->generateOverloadRecommendations(
                    $item,
                    $analysis,
                    $startDate,
                    $endDate,
                ));
            } elseif ($item['status'] === 'critical_low') {
                $recommendations = array_merge($recommendations, $this->generateUnderutilizationRecommendations(
                    $item,
                    $analysis,
                    $startDate,
                    $endDate,
                ));
            }
        }

        // Recommandations pour les surcharges non critiques
        foreach ($analysis['overloaded'] as $item) {
            $recommendations = array_merge($recommendations, $this->generateOverloadRecommendations(
                $item,
                $analysis,
                $startDate,
                $endDate,
            ));
        }

        // Recommandations pour les sous-utilisations
        foreach ($analysis['underutilized'] as $item) {
            $recommendations = array_merge($recommendations, $this->generateUnderutilizationRecommendations(
                $item,
                $analysis,
                $startDate,
                $endDate,
            ));
        }

        // Trier par priorité (score)
        usort($recommendations, fn ($a, $b): int => $b['priority_score'] <=> $a['priority_score']);

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
    private function generateOverloadRecommendations(
        array $item,
        array $fullAnalysis,
        DateTime $start,
        DateTime $end,
    ): array {
        /** @var Contributor $contributor */
        $contributor = $item['contributor'];
        $tace        = $item['tace'];
        $severity    = $item['severity'];

        $recommendations = [];

        // Récupérer les plannings actuels du contributeur
        $planningRepo = $this->entityManager->getRepository(PlanningEntity::class);
        $plannings    = $planningRepo
            ->createQueryBuilder('p')
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
            $project   = $planning->getProject();
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

        // Trier par priorité : projets internes d'abord (à décharger en premier),
        // puis par niveau de service client croissant, puis par heures décroissantes
        usort($projectsWorkload, function ($a, $b) {
            $aInternal = $a['project']->isInternal;
            $bInternal = $b['project']->isInternal;

            // Les projets internes sont toujours déchargés en premier
            if ($aInternal !== $bInternal) {
                return $bInternal <=> $aInternal; // true (1) avant false (0) → interne d'abord
            }

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
                        sprintf(
                            'Augmenter l\'allocation de %s sur %s',
                            $targetContributor->getFullName(),
                            $project->getName(),
                        ),
                    ],
                    'expected_impact' => sprintf(
                        'Réduction estimée du TACE de %s : %.1f points',
                        $contributor->getFullName(),
                        $targetReduction / count($projectsWorkload),
                    ),
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
    private function generateUnderutilizationRecommendations(
        array $item,
        array $fullAnalysis,
        DateTime $start,
        DateTime $end,
    ): array {
        /** @var Contributor $contributor */
        $contributor = $item['contributor'];
        $tace        = $item['tace'];
        $severity    = $item['severity'];

        $recommendations = [];

        // Trouver des projets externes en cours qui pourraient bénéficier de ressources supplémentaires
        $activeProjects = $this->projectRepository->findActiveExternalOrderedByName();

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
                    'expected_impact' => sprintf(
                        'Augmentation estimée du TACE : %.1f points',
                        abs($item['deviation']) / 3,
                    ),
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
        // Extraire les IDs des profils du contributeur source
        $sourceProfileIds = array_map(fn ($p) => $p->getId(), $source->getProfiles()->toArray());
        $compatible       = [];

        foreach ($candidates as $candidate) {
            /** @var Contributor $targetContributor */
            $targetContributor = $candidate['contributor'];

            if ($targetContributor->getId() === $source->getId()) {
                continue;
            }

            // Vérifier les profils communs (par ID)
            $targetProfileIds = array_map(fn ($p) => $p->getId(), $targetContributor->getProfiles()->toArray());
            $commonProfileIds = array_intersect($sourceProfileIds, $targetProfileIds);

            if (count($commonProfileIds) > 0) {
                $compatible[] = $candidate;
            }
        }

        // Trier par TACE croissant (les plus sous-utilisés en premier)
        usort($compatible, fn ($a, $b): int => $a['tace'] <=> $b['tace']);

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
            'total_contributors' => count($analysis['critical'])
                    + count($analysis['overloaded'])
                    + count($analysis['underutilized'])
                    + count($analysis['optimal']),
            'critical_count'        => count($analysis['critical']),
            'overloaded_count'      => count($analysis['overloaded']),
            'underutilized_count'   => count($analysis['underutilized']),
            'optimal_count'         => count($analysis['optimal']),
            'total_recommendations' => count($recommendations),
            'high_priority_count'   => count(array_filter(
                $recommendations,
                fn ($r): bool => $r['severity_level'] === 'critical' || $r['severity_level'] === 'high',
            )),
            'medium_priority_count' => count(array_filter(
                $recommendations,
                fn ($r): bool => $r['severity_level'] === 'medium',
            )),
            'low_priority_count' => count(array_filter(
                $recommendations,
                fn ($r): bool => $r['severity_level'] === 'low',
            )),
            'critical_workload_count' => count($analysis['critical']),
            'contributors_analyzed'   => count($analysis['critical'])
                    + count($analysis['overloaded'])
                    + count($analysis['underutilized'])
                    + count($analysis['optimal']),
        ];
    }

    /**
     * Applique une recommandation d'optimisation en créant ou modifiant des plannings.
     *
     * @return array Résultat de l'application avec succès et message
     */
    public function applyRecommendation(array $recommendation, DateTime $startDate, DateTime $endDate): array
    {
        $type = $recommendation['type'] ?? null;

        if (!$type) {
            return [
                'success' => false,
                'message' => 'Type de recommandation invalide',
            ];
        }

        try {
            return match ($type) {
                'increase_allocation' => $this->applyIncreaseAllocation($recommendation, $startDate, $endDate),
                'reassign_planning'   => $this->applyReassignPlanning($recommendation, $startDate, $endDate),
                'reduce_workload'     => [
                    'success' => false,
                    'message' => 'La réduction de charge doit être effectuée manuellement',
                ],
                default => [
                    'success' => false,
                    'message' => 'Type de recommandation non supporté: '.$type,
                ],
            };
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'application: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Applique une recommandation d'augmentation d'allocation.
     */
    private function applyIncreaseAllocation(array $recommendation, DateTime $startDate, DateTime $endDate): array
    {
        $contributor = $recommendation['contributor'] ?? null;
        $project     = $recommendation['project']     ?? null;

        if (!$contributor || !$project) {
            return [
                'success' => false,
                'message' => 'Données de recommandation incomplètes',
            ];
        }

        // Créer un planning de 4h/jour sur la période
        $planning = new PlanningEntity();
        $planning->setCompany($this->companyContext->getCurrentCompany());
        $planning->setContributor($contributor);
        $planning->setProject($project);
        $planning->setStartDate($startDate);
        $planning->setEndDate($endDate);
        $planning->setDailyHours('4.00'); // 50% d'allocation par défaut
        $planning->setStatus('planned');
        $planning->setNotes('Planning créé automatiquement depuis les recommandations d\'optimisation');

        $this->entityManager->persist($planning);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => sprintf(
                'Planning créé: %s alloué à %s (4h/jour)',
                $contributor->getFullName(),
                $project->getName(),
            ),
            'planning_id' => $planning->getId(),
        ];
    }

    /**
     * Applique une recommandation de réaffectation.
     */
    private function applyReassignPlanning(array $recommendation, DateTime $startDate, DateTime $endDate): array
    {
        $sourceContributor = $recommendation['contributor'] ?? null;
        $targetContributor = $recommendation['target']      ?? null;
        $project           = $recommendation['project']     ?? null;

        if (!$sourceContributor || !$targetContributor || !$project) {
            return [
                'success' => false,
                'message' => 'Données de recommandation incomplètes',
            ];
        }

        // Récupérer le planning existant du contributeur source
        $planningRepo      = $this->entityManager->getRepository(PlanningEntity::class);
        $existingPlannings = $planningRepo
            ->createQueryBuilder('p')
            ->where('p.contributor = :contributor')
            ->andWhere('p.project = :project')
            ->andWhere('p.startDate <= :end')
            ->andWhere('p.endDate >= :start')
            ->setParameter('contributor', $sourceContributor)
            ->setParameter('project', $project)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();

        if (empty($existingPlannings)) {
            return [
                'success' => false,
                'message' => 'Aucun planning trouvé à réaffecter',
            ];
        }

        $messages         = [];
        $planningIds      = [];
        $existingPlanning = $existingPlannings[0]; // Prendre le premier

        // Réduire l'allocation du contributeur source de 50%
        $currentHours = (float) $existingPlanning->getDailyHours();
        $newHours     = $currentHours / 2;
        $existingPlanning->setDailyHours((string) $newHours);
        $existingPlanning->setNotes(
            ($existingPlanning->getNotes() ?? '')
            ."\n[Optimisation auto] Allocation réduite de {$currentHours}h à {$newHours}h/jour",
        );
        $this->entityManager->flush();

        $messages[]    = sprintf('Allocation de %s réduite à %.1fh/jour', $sourceContributor->getFullName(), $newHours);
        $planningIds[] = $existingPlanning->getId();

        // Créer un planning pour le contributeur cible
        $newPlanning = new PlanningEntity();
        $newPlanning->setCompany($this->companyContext->getCurrentCompany());
        $newPlanning->setContributor($targetContributor);
        $newPlanning->setProject($project);
        $newPlanning->setStartDate($startDate);
        $newPlanning->setEndDate($endDate);
        $newPlanning->setDailyHours((string) $newHours); // Même allocation
        $newPlanning->setStatus('planned');
        $newPlanning->setNotes(
            'Planning créé automatiquement par réaffectation depuis '.$sourceContributor->getFullName(),
        );

        $this->entityManager->persist($newPlanning);
        $this->entityManager->flush();

        $messages[]    = sprintf('Planning créé pour %s (%.1fh/jour)', $targetContributor->getFullName(), $newHours);
        $planningIds[] = $newPlanning->getId();

        return [
            'success'      => true,
            'message'      => implode(', ', $messages),
            'planning_ids' => $planningIds,
        ];
    }
}

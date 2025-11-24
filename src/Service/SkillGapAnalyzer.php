<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ContributorSkill;
use App\Entity\Project;
use App\Entity\Skill;
use App\Repository\ContributorSkillRepository;
use App\Repository\ProjectRepository;
use App\Repository\SkillRepository;

class SkillGapAnalyzer
{
    public function __construct(
        private SkillRepository $skillRepository,
        private ContributorSkillRepository $contributorSkillRepository,
        private ProjectRepository $projectRepository
    ) {
    }

    /**
     * Analyse globale des écarts de compétences.
     * Compare les compétences disponibles vs les compétences requises (projets actifs).
     *
     * @return array{gaps: array, surplus: array, balanced: array}
     */
    public function analyzeGlobalGaps(): array
    {
        $allSkills = $this->skillRepository->findActive();
        $gaps      = [];
        $surplus   = [];
        $balanced  = [];

        foreach ($allSkills as $skill) {
            // Compter les contributeurs ayant cette compétence au niveau confirmé ou expert
            $availableCount = count($this->contributorSkillRepository->findBySkillAndMinLevel(
                $skill,
                ContributorSkill::LEVEL_CONFIRMED,
            ));

            // Estimer le besoin (simplifié : nombre de projets actifs utilisant les technologies liées)
            // Dans un système plus avancé, on pourrait avoir des compétences requises par projet
            $demandCount = $this->estimateSkillDemand($skill);

            $gap = $demandCount - $availableCount;

            if ($gap > 0) {
                $gaps[] = [
                    'skill'     => $skill,
                    'available' => $availableCount,
                    'demand'    => $demandCount,
                    'gap'       => $gap,
                    'severity'  => $this->calculateGapSeverity($gap, $demandCount),
                ];
            } elseif ($gap < 0) {
                $surplus[] = [
                    'skill'     => $skill,
                    'available' => $availableCount,
                    'demand'    => $demandCount,
                    'surplus'   => abs($gap),
                ];
            } else {
                $balanced[] = [
                    'skill'     => $skill,
                    'available' => $availableCount,
                    'demand'    => $demandCount,
                ];
            }
        }

        // Trier les gaps par sévérité décroissante
        usort($gaps, fn ($a, $b) => $b['gap'] <=> $a['gap']);

        return [
            'gaps'     => $gaps,
            'surplus'  => $surplus,
            'balanced' => $balanced,
            'summary'  => [
                'totalGaps'     => count($gaps),
                'criticalGaps'  => count(array_filter($gaps, fn ($g) => $g['severity'] === 'critical')),
                'totalSurplus'  => count($surplus),
                'totalBalanced' => count($balanced),
            ],
        ];
    }

    /**
     * Analyse les écarts de compétences pour un projet donné.
     *
     * @return array{requiredSkills: array, missingSkills: array, recommendations: array}
     */
    public function analyzeProjectGaps(Project $project): array
    {
        // Récupérer les technologies du projet
        $projectTechnologies = $project->getTechnologies();
        $requiredSkills      = [];
        $missingSkills       = [];

        foreach ($projectTechnologies as $technology) {
            // Mapper les technologies vers les compétences (simplifié : recherche par nom)
            $skills = $this->skillRepository->search($technology->getName());

            foreach ($skills as $skill) {
                // Vérifier si on a des contributeurs disponibles avec cette compétence
                $availableContributors = $this->contributorSkillRepository->findBySkillAndMinLevel(
                    $skill,
                    ContributorSkill::LEVEL_INTERMEDIATE,
                );

                $requiredSkills[] = [
                    'skill'          => $skill,
                    'technology'     => $technology,
                    'availableCount' => count($availableContributors),
                ];

                if (count($availableContributors) === 0) {
                    $missingSkills[] = [
                        'skill'          => $skill,
                        'technology'     => $technology,
                        'recommendation' => 'Formation urgente ou recrutement nécessaire',
                    ];
                }
            }
        }

        // Générer des recommandations
        $recommendations = $this->generateProjectRecommendations($project, $missingSkills, $requiredSkills);

        return [
            'requiredSkills'  => $requiredSkills,
            'missingSkills'   => $missingSkills,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Identifie les besoins de formation.
     *
     * @return array{trainingNeeds: array, totalNeeds: int}
     */
    public function identifyTrainingNeeds(): array
    {
        $gaps          = $this->analyzeGlobalGaps();
        $trainingNeeds = [];

        foreach ($gaps['gaps'] as $gap) {
            if ($gap['severity'] === 'critical' || $gap['severity'] === 'high') {
                // Trouver les contributeurs qui ont cette compétence à un niveau débutant ou intermédiaire
                $contributors = $this->contributorSkillRepository->findBySkill($gap['skill']);

                $contributorsToTrain = [];
                foreach ($contributors as $contributorSkill) {
                    $level = $contributorSkill->getEffectiveLevel();
                    if ($level < ContributorSkill::LEVEL_CONFIRMED) {
                        $contributorsToTrain[] = [
                            'contributor'  => $contributorSkill->getContributor(),
                            'currentLevel' => $level,
                            'targetLevel'  => ContributorSkill::LEVEL_CONFIRMED,
                        ];
                    }
                }

                $trainingNeeds[] = [
                    'skill'               => $gap['skill'],
                    'gap'                 => $gap['gap'],
                    'severity'            => $gap['severity'],
                    'contributorsToTrain' => $contributorsToTrain,
                    'priority'            => $gap['severity'],
                ];
            }
        }

        return [
            'trainingNeeds' => $trainingNeeds,
            'totalNeeds'    => count($trainingNeeds),
        ];
    }

    /**
     * Recommandations de recrutement basées sur les gaps.
     *
     * @return array{recruitmentNeeds: array}
     */
    public function getRecruitmentRecommendations(): array
    {
        $gaps             = $this->analyzeGlobalGaps();
        $recruitmentNeeds = [];

        foreach ($gaps['gaps'] as $gap) {
            // Si le gap est important et qu'on n'a pas assez de contributeurs à former
            if ($gap['gap'] >= 2 && $gap['severity'] !== 'low') {
                $recruitmentNeeds[] = [
                    'skill'            => $gap['skill'],
                    'gap'              => $gap['gap'],
                    'severity'         => $gap['severity'],
                    'recommendedHires' => (int) ceil($gap['gap'] / 2),
                    'urgency'          => $gap['severity'] === 'critical' ? 'Immédiat' : 'Court terme',
                ];
            }
        }

        // Trier par sévérité
        usort($recruitmentNeeds, function ($a, $b) {
            $severityOrder = ['critical' => 3, 'high' => 2, 'medium' => 1, 'low' => 0];

            return $severityOrder[$b['severity']] <=> $severityOrder[$a['severity']];
        });

        return [
            'recruitmentNeeds' => $recruitmentNeeds,
            'totalPositions'   => array_sum(array_column($recruitmentNeeds, 'recommendedHires')),
        ];
    }

    /**
     * Estime la demande pour une compétence (simplifié).
     */
    private function estimateSkillDemand(Skill $skill): int
    {
        // Version simplifiée : compter les projets actifs
        // Dans une version avancée, on pourrait avoir un mapping compétence → projet
        $activeProjects = $this->projectRepository->findBy(['status' => 'active']);

        $demandCount = 0;
        foreach ($activeProjects as $project) {
            // Rechercher si la compétence correspond à une technologie du projet
            foreach ($project->getTechnologies() as $technology) {
                if (stripos($technology->getName(), $skill->getName())    !== false
                    || stripos($skill->getName(), $technology->getName()) !== false) {
                    ++$demandCount;
                    break;
                }
            }
        }

        return $demandCount;
    }

    /**
     * Calcule la sévérité d'un gap.
     */
    private function calculateGapSeverity(int $gap, int $demand): string
    {
        if ($demand === 0) {
            return 'low';
        }

        $gapPercentage = ($gap / $demand) * 100;

        if ($gapPercentage >= 75) {
            return 'critical';
        }
        if ($gapPercentage >= 50) {
            return 'high';
        }
        if ($gapPercentage >= 25) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Génère des recommandations pour un projet.
     *
     * @return array<int, array{type: string, priority: string, message: string, actions: array}>
     */
    private function generateProjectRecommendations(Project $project, array $missingSkills, array $requiredSkills): array
    {
        $recommendations = [];

        if (count($missingSkills) > 0) {
            $recommendations[] = [
                'type'     => 'critical',
                'priority' => 'high',
                'message'  => sprintf(
                    'Le projet manque de %d compétence(s) critique(s)',
                    count($missingSkills),
                ),
                'actions' => [
                    'Former des contributeurs sur ces technologies',
                    'Recruter un profil avec ces compétences',
                    'Faire appel à un freelance spécialisé',
                ],
            ];
        }

        // Vérifier si certaines compétences ont peu de contributeurs disponibles
        $lowAvailability = array_filter($requiredSkills, fn ($rs) => $rs['availableCount'] > 0 && $rs['availableCount'] <= 2);
        if (count($lowAvailability) > 0) {
            $recommendations[] = [
                'type'     => 'warning',
                'priority' => 'medium',
                'message'  => sprintf(
                    '%d compétence(s) avec peu de contributeurs disponibles (risque de goulot d\'étranglement)',
                    count($lowAvailability),
                ),
                'actions' => [
                    'Former des contributeurs juniors sur ces technologies',
                    'Anticiper la charge et planifier les ressources',
                ],
            ];
        }

        if (count($recommendations) === 0) {
            $recommendations[] = [
                'type'     => 'success',
                'priority' => 'low',
                'message'  => 'Le projet dispose de toutes les compétences nécessaires',
                'actions'  => [],
            ];
        }

        return $recommendations;
    }
}

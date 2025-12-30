<?php

declare(strict_types=1);

namespace App\AI\Tool;

use App\Repository\ProjectRepository;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

/**
 * Tool pour récupérer les statistiques d'un type de projet.
 *
 * Fournit aux agents IA des statistiques sur les projets similaires
 * pour améliorer les estimations de devis.
 */
#[AsTool('get_project_stats', "Récupère les statistiques d'un type de projet (forfait, régie, etc.)")]
final readonly class ProjectStatsTool
{
    public function __construct(
        private ProjectRepository $projectRepository,
    ) {
    }

    /**
     * @return array{
     *     project_type: string,
     *     total_projects: int,
     *     stats?: array{
     *         avg_duration_days: float,
     *         avg_budget: float,
     *         common_statuses: array<string, int>
     *     },
     *     error?: string
     * }
     */
    public function __invoke(string $projectType = 'forfait'): array
    {
        // Valider le type de projet
        $validTypes = ['forfait', 'regie', 'maintenance'];
        if (!in_array($projectType, $validTypes, true)) {
            return [
                'project_type'   => $projectType,
                'total_projects' => 0,
                'error'          => 'Type de projet invalide. Valeurs acceptées: '.implode(', ', $validTypes),
            ];
        }

        // Récupérer les projets du type demandé
        $projects = $this->projectRepository->findBy(['type' => $projectType]);

        if (empty($projects)) {
            return [
                'project_type'   => $projectType,
                'total_projects' => 0,
                'stats'          => [
                    'avg_duration_days' => 0.0,
                    'avg_budget'        => 0.0,
                    'common_statuses'   => [],
                ],
            ];
        }

        // Calculer les statistiques
        $totalDuration     = 0;
        $totalBudget       = 0.0;
        $statusCount       = [];
        $projectsWithDates = 0;

        foreach ($projects as $project) {
            // Durée moyenne (si dates disponibles)
            if ($project->getStartDate() && $project->getEndDate()) {
                $duration = $project->getStartDate()->diff($project->getEndDate())->days;
                $totalDuration += $duration;
                ++$projectsWithDates;
            }

            // Budget moyen
            $totalBudget += $project->getBudget() ?? 0.0;

            // Statuts les plus fréquents
            $status               = $project->getStatus();
            $statusCount[$status] = ($statusCount[$status] ?? 0) + 1;
        }

        $totalProjects = count($projects);
        $avgDuration   = $projectsWithDates > 0 ? $totalDuration / $projectsWithDates : 0;
        $avgBudget     = $totalProjects     > 0 ? $totalBudget   / $totalProjects : 0;

        // Trier les statuts par fréquence
        arsort($statusCount);

        return [
            'project_type'   => $projectType,
            'total_projects' => $totalProjects,
            'stats'          => [
                'avg_duration_days' => round($avgDuration, 1),
                'avg_budget'        => round($avgBudget, 2),
                'common_statuses'   => $statusCount,
            ],
        ];
    }
}

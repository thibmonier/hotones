<?php

declare(strict_types=1);

namespace App\AI\Tool;

use App\Repository\ClientRepository;
use App\Repository\ProjectRepository;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

/**
 * Tool pour récupérer l'historique de projets d'un client.
 *
 * Permet aux agents IA d'accéder automatiquement aux informations
 * historiques d'un client pour générer des devis contextualisés.
 */
#[AsTool('get_client_history', "Récupère l'historique de projets d'un client par son nom")]
final readonly class ClientHistoryTool
{
    public function __construct(
        private ClientRepository $clientRepository,
        private ProjectRepository $projectRepository,
    ) {
    }

    /**
     * @return array{
     *     client_name: string,
     *     client_found: bool,
     *     total_projects: int,
     *     recent_projects: array<int, array{name: string, status: string, type: string}>,
     *     service_level: string|null,
     *     error?: string
     * }
     */
    public function __invoke(string $clientName): array
    {
        $clients = $this->clientRepository->search($clientName, 1);

        if (empty($clients)) {
            return [
                'client_name'     => $clientName,
                'client_found'    => false,
                'total_projects'  => 0,
                'recent_projects' => [],
                'service_level'   => null,
                'error'           => 'Client non trouvé',
            ];
        }

        $client = $clients[0];

        // Récupérer tous les projets du client
        $projects = $this->projectRepository->findBy(['client' => $client], ['createdAt' => 'DESC']);

        // Formater les 5 derniers projets
        $recentProjects = array_slice(array_map(fn ($project): array => [
            'name'   => $project->getName(),
            'status' => $project->getStatus(),
            'type'   => $project->getProjectType(),
        ], $projects), 0, 5);

        return [
            'client_name'     => $client->getName(),
            'client_found'    => true,
            'total_projects'  => count($projects),
            'recent_projects' => $recentProjects,
            'service_level'   => $client->getServiceLevel(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ClientRepository;
use App\Repository\ContributorRepository;
use App\Repository\OrderRepository;
use App\Repository\ProjectRepository;

class GlobalSearchService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ContributorRepository $contributorRepository,
        private readonly OrderRepository $orderRepository,
        private readonly ClientRepository $clientRepository
    ) {
    }

    /**
     * Recherche globale dans toutes les entités.
     *
     * @return array<string, array<int, mixed>>
     */
    public function search(string $query, int $limit = 5): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $results = [];

        // Recherche dans les projets
        $projects = $this->projectRepository->search($query, $limit);
        if (count($projects) > 0) {
            $results['projects'] = $projects;
        }

        // Recherche dans les contributeurs
        $contributors = $this->contributorRepository->search($query, $limit);
        if (count($contributors) > 0) {
            $results['contributors'] = $contributors;
        }

        // Recherche dans les devis
        $orders = $this->orderRepository->search($query, $limit);
        if (count($orders) > 0) {
            $results['orders'] = $orders;
        }

        // Recherche dans les clients
        $clients = $this->clientRepository->search($query, $limit);
        if (count($clients) > 0) {
            $results['clients'] = $clients;
        }

        return $results;
    }
}

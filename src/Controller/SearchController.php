<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GlobalSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_INTERVENANT')]
class SearchController extends AbstractController
{
    public function __construct(
        private readonly GlobalSearchService $searchService,
    ) {
    }

    #[Route('/search', name: 'global_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->render('search/index.html.twig', [
                'query'   => $query,
                'results' => [],
            ]);
        }

        $results = $this->searchService->search($query);

        return $this->render('search/index.html.twig', [
            'query'   => $query,
            'results' => $results,
        ]);
    }

    #[Route('/api/search', name: 'api_global_search', methods: ['GET'])]
    public function apiSearch(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $results = $this->searchService->search($query);

        // Format results for JSON response
        $formatted = [];

        foreach ($results as $type => $items) {
            $formatted[$type] = [];
            foreach ($items as $item) {
                $formatted[$type][] = [
                    'id'   => $item->getId(),
                    'name' => $this->getItemName($item),
                    'url'  => $this->getItemUrl($type, $item->getId()),
                ];
            }
        }

        return $this->json($formatted);
    }

    private function getItemName(object $item): string
    {
        return match (true) {
            method_exists($item, 'getName')      => $item->getName(),
            method_exists($item, 'getFullName')  => $item->getFullName(),
            method_exists($item, 'getReference') => $item->getReference(),
            default                              => (string) $item,
        };
    }

    private function getItemUrl(string $type, int $id): string
    {
        return match ($type) {
            'projects'     => $this->generateUrl('project_show', ['id' => $id]),
            'contributors' => $this->generateUrl('contributor_show', ['id' => $id]),
            'orders'       => $this->generateUrl('order_show', ['id' => $id]),
            'clients'      => $this->generateUrl('client_show', ['id' => $id]),
            default        => '#',
        };
    }
}

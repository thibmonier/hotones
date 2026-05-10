<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Contributor\Query\ListActiveContributors\ListActiveContributorsHandler;
use App\Application\Contributor\Query\ListActiveContributors\ListActiveContributorsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Sprint-018 Phase 3 strangler fig Contributor BC.
 *
 * Endpoints API JSON utilisant exclusivement les UC DDD via ACL Phase 2
 * (`DoctrineDddContributorRepository`). N'accède jamais directement à la
 * couche flat (`App\Repository\ContributorRepository`).
 *
 * Existant : `ContributorController` reste actif sur la couche flat (lecture
 * paginée Twig + écriture). Phase 4 fusionnera les deux quand mutations DDD
 * seront prêtes.
 *
 * @see ADR-0008 ACL pattern
 */
#[Route('/api/contributors')]
#[IsGranted('ROLE_USER')]
final class ContributorDddApiController extends AbstractController
{
    public function __construct(
        private readonly ListActiveContributorsHandler $listActiveContributors,
    ) {
    }

    /**
     * GET /api/contributors/active.
     *
     * Retourne la liste des contributeurs actifs (status `active`) sous forme
     * JSON. Pas de pagination Phase 3 (volumétrie agence ~50 contributeurs).
     */
    #[Route('/active', name: 'api_contributor_list_active', methods: ['GET'])]
    public function listActive(): JsonResponse
    {
        $items = ($this->listActiveContributors)(new ListActiveContributorsQuery());

        return new JsonResponse([
            'data' => array_map(static fn ($dto): array => $dto->toArray(), $items),
            'meta' => ['count' => count($items)],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/validate')]
#[IsGranted('ROLE_USER')]
class ValidationController extends AbstractController
{
    public function __construct(
        private readonly ClientRepository $clientRepository
    ) {
    }

    /**
     * Endpoint de validation AJAX générique.
     */
    #[Route('', name: 'api_validate', methods: ['POST'])]
    public function validate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['type'], $data['value'], $data['field'])) {
            return new JsonResponse([
                'valid'   => false,
                'message' => 'Données invalides',
            ], Response::HTTP_BAD_REQUEST);
        }

        $type  = $data['type'];
        $value = $data['value'];
        $field = $data['field'];

        // ID de l'entité en cours d'édition (pour exclure de la vérification d'unicité)
        $excludeId = $data['exclude_id'] ?? null;

        return match ($type) {
            'client_name_unique' => $this->validateClientNameUnique($value, $excludeId),
            default              => new JsonResponse([
                'valid'   => false,
                'message' => 'Type de validation inconnu',
            ], Response::HTTP_BAD_REQUEST),
        };
    }

    /**
     * Valide l'unicité d'un nom de client.
     */
    private function validateClientNameUnique(string $name, ?int $excludeId): JsonResponse
    {
        $client = $this->clientRepository->findOneBy(['name' => $name]);

        if ($client && $client->getId() !== $excludeId) {
            return new JsonResponse([
                'valid'   => false,
                'message' => 'Un client avec ce nom existe déjà',
            ]);
        }

        return new JsonResponse([
            'valid'   => true,
            'message' => '✓ Nom de client disponible',
        ]);
    }
}

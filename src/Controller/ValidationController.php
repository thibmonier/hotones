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
            'email'              => $this->validateEmail($value),
            'siret'              => $this->validateSiret($value, $excludeId),
            'phone'              => $this->validatePhone($value),
            'url'                => $this->validateUrl($value),
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

    /**
     * Valide un email.
     */
    private function validateEmail(string $email): JsonResponse
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'valid'   => false,
                'message' => 'Format d\'email invalide',
            ]);
        }

        return new JsonResponse([
            'valid'   => true,
            'message' => '✓ Email valide',
        ]);
    }

    /**
     * Valide un SIRET.
     */
    private function validateSiret(string $siret, ?int $excludeId): JsonResponse
    {
        // Remove spaces
        $siret = str_replace(' ', '', $siret);

        // Check length
        if (strlen($siret) !== 14) {
            return new JsonResponse([
                'valid'   => false,
                'message' => 'Le SIRET doit contenir 14 chiffres',
            ]);
        }

        // Check numeric
        if (!ctype_digit($siret)) {
            return new JsonResponse([
                'valid'   => false,
                'message' => 'Le SIRET ne doit contenir que des chiffres',
            ]);
        }

        // Check uniqueness - Disabled: Client entity doesn't have siret field
        // TODO: Add siret field to Client entity or remove this validation
        // $client = $this->clientRepository->findOneBy(['siret' => $siret]);
        // if ($client && $client->getId() !== $excludeId) {
        //     return new JsonResponse([
        //         'valid'   => false,
        //         'message' => 'Ce SIRET est déjà enregistré',
        //     ]);
        // }

        return new JsonResponse([
            'valid'   => true,
            'message' => '✓ SIRET valide',
        ]);
    }

    /**
     * Valide un numéro de téléphone.
     */
    private function validatePhone(string $phone): JsonResponse
    {
        // Remove formatting characters
        $cleaned = preg_replace('/[\s\-\.\(\)]/', '', $phone);

        // Validate French phone number format
        if (!preg_match('/^(\+33|0)[1-9]\d{8}$/', $cleaned)) {
            return new JsonResponse([
                'valid'   => false,
                'message' => 'Format de téléphone invalide (ex: 01 23 45 67 89)',
            ]);
        }

        return new JsonResponse([
            'valid'   => true,
            'message' => '✓ Téléphone valide',
        ]);
    }

    /**
     * Valide une URL.
     */
    private function validateUrl(string $url): JsonResponse
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new JsonResponse([
                'valid'   => false,
                'message' => 'Format d\'URL invalide',
            ]);
        }

        return new JsonResponse([
            'valid'   => true,
            'message' => '✓ URL valide',
        ]);
    }
}

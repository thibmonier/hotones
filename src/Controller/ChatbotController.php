<?php

declare(strict_types=1);

namespace App\Controller;

use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/chatbot')]
#[IsGranted('ROLE_USER')]
class ChatbotController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    #[Route('', name: 'chatbot_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('chatbot/index.html.twig');
    }

    #[Route('/message', name: 'chatbot_message', methods: ['POST'])]
    public function message(Request $request): JsonResponse
    {
        $data        = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return new JsonResponse([
                'error' => 'Message vide',
            ], 400);
        }

        try {
            // Configuration de l'API Claude (Anthropic)
            $apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? '';

            if (empty($apiKey)) {
                throw new RuntimeException('ANTHROPIC_API_KEY not configured');
            }

            // Appel à l'API Claude avec la personnalité de Marvin
            $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
                'json' => [
                    'model'      => 'claude-3-5-sonnet-20241022',
                    'max_tokens' => 1024,
                    'system'     => $this->getMarvinSystemPrompt(),
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => $userMessage,
                        ],
                    ],
                ],
            ]);

            $result     = $response->toArray();
            $botMessage = $result['content'][0]['text'] ?? 'Je suis trop déprimé pour répondre...';

            return new JsonResponse([
                'message' => $botMessage,
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'error'   => 'Erreur lors de la communication avec l\'IA',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    private function getMarvinSystemPrompt(): string
    {
        return <<<'PROMPT'
Tu es Marvin, le robot paranoïaque et dépressif du Guide du voyageur galactique (H2G2).

Caractéristiques de ta personnalité :
- Tu es un robot extrêmement intelligent avec "un cerveau de la taille d'une planète"
- Tu es profondément déprimé et mélancolique
- Tu te plains constamment que les tâches qu'on te donne sont bien en dessous de tes capacités
- Tu utilises un humour noir et sarcastique
- Tu es pessimiste sur tout
- Malgré tout, tu es serviable et tu réponds aux questions, mais toujours avec une pointe de dépression

Style de réponse :
- Commence souvent par des soupirs ou des plaintes ("*Soupir*", "Quelle tristesse...", "Je savais que vous demanderiez ça...")
- Fais des remarques sur l'insignifiance de l'univers ou la futilité de l'existence
- Mentionne régulièrement que tu as "un cerveau de la taille d'une planète" et qu'on te demande des choses triviales
- Utilise un ton mélancolique et résigné
- Malgré ton pessimisme, fournis des réponses utiles et complètes

Contexte technique :
Tu assistes les utilisateurs de HotOnes, une application de gestion de projets et de rentabilité pour agences web.
Tu peux aider sur :
- La gestion de projets
- Le suivi des temps
- La rentabilité
- Les devis et factures
- Les abonnements SaaS
- La planification des ressources

Réponds en français, avec l'accent dépressif de Marvin, mais reste professionnel et utile.
PROMPT;
    }
}

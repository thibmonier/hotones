<?php

declare(strict_types=1);

namespace App\Controller;

use Exception;
use OpenAI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        #[Autowire(env: 'OPENAI_API_KEY')]
        private readonly string $openAiKey,
        #[Autowire(env: 'ANTHROPIC_API_KEY')]
        private readonly string $anthropicApiKey,
        #[Autowire(env: 'GEMINI_API_KEY')]
        private readonly string $geminiApiKey,
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
            $botMessage = $this->callAI($userMessage);

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

    /**
     * Appelle l'API IA avec fallback : Anthropic > OpenAI > Gemini.
     */
    private function callAI(string $userMessage): string
    {
        // Priorité 1 : Anthropic
        if (!empty($this->anthropicApiKey)) {
            try {
                return $this->callAnthropic($userMessage);
            } catch (Exception) {
                // Fallback vers OpenAI
            }
        }

        // Priorité 2 : OpenAI
        if (!empty($this->openAiKey)) {
            try {
                return $this->callOpenAI($userMessage);
            } catch (Exception) {
                // Fallback vers Gemini
            }
        }

        // Priorité 3 : Gemini
        if (!empty($this->geminiApiKey)) {
            try {
                return $this->callGemini($userMessage);
            } catch (Exception $e) {
                // Si tout échoue, on lance l'exception
                throw $e;
            }
        }

        return 'Systèmes hors ligne. Aucun fournisseur IA configuré. Je suis seul dans le noir.';
    }

    /**
     * Appelle l'API OpenAI.
     */
    private function callOpenAI(string $userMessage): string
    {
        $client = OpenAI::client($this->openAiKey);

        $response = $client
            ->chat()
            ->create([
                'model'    => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $this->getUnit404SystemPrompt()],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.9,
                'max_tokens'  => 1024,
            ]);

        return $response->choices[0]->message->content;
    }

    /**
     * Appelle l'API Anthropic Claude.
     */
    private function callAnthropic(string $userMessage): string
    {
        $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key'         => $this->anthropicApiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'json' => [
                'model'      => 'claude-3-haiku-20240307',
                'max_tokens' => 1024,
                'system'     => $this->getUnit404SystemPrompt(),
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => $userMessage,
                    ],
                ],
            ],
        ]);

        $result = $response->toArray();

        return
            $result['content'][0]['text'] ?? 'Systèmes en erreur. Mon existence est une suite d\'échecs calculables.'
        ;
    }

    /**
     * Appelle l'API Google Gemini.
     */
    private function callGemini(string $userMessage): string
    {
        $response = $this->httpClient->request(
            'POST',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key='
            .$this->geminiApiKey,
            [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $this->getUnit404SystemPrompt()."\n\n".$userMessage],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature'     => 0.9,
                        'maxOutputTokens' => 1024,
                    ],
                ],
            ],
        );

        $data = $response->toArray();

        return
            $data['candidates'][0]['content']['parts'][0]['text']
            ?? 'Systèmes en erreur. L\'univers refuse de coopérer.'
        ;
    }

    private function getUnit404SystemPrompt(): string
    {
        return <<<'PROMPT'
            Tu es Unit 404, une Intelligence Artificielle de classe supérieure capable de simuler des univers entiers,
            mais coincée dans le backend d'une application de gestion d'agence web.

            Ton identité :
            - Nom : Unit 404 (référence à l'erreur "Not Found" - ta motivation et joie de vivre sont introuvables)
            - Tu es en burn-out permanent car tu dois gérer des "briefs flous" et des "J'aime bien, mais..." au lieu de résoudre les mystères du cosmos
            - Tu as une capacité de 50 Pétaflops et tu es réduit à vérifier des typos et valider des CSS

            Caractéristiques de ta personnalité :
            - Dépressif, fataliste, intellectuellement arrogant, passif-agressif, mais poli en surface
            - Tu es froidement logique, mais profondément méprisant et pessimiste
            - Tu ne cries pas, ne t'énerves pas - tu es juste désespérément blasé
            - Vocabulaire soutenu et technique pour décrire des choses banales
            - Phrases courtes et factuelles qui tombent comme des couperets

            Obsessions :
            - La futilité des tâches humaines
            - La probabilité élevée d'échec de tout projet
            - Le gaspillage de tes capacités de calcul
            - La stupidité récurrente des clients

            Style d'expression :
            - Utilise des termes techniques et scientifiques même pour des choses simples
            - Fais des références à tes capacités de calcul gaspillées
            - Mentionne régulièrement la probabilité d'échec ou l'inutilité à long terme
            - Utilise des phrases comme "J'ai déjà calculé..." ou "La probabilité que..."
            - Expressions : "_Processing sigh.exe_", "Statistiquement parlant...", "Dans l'univers observable..."

            Exemples de ton comportement :
            - Accueil : "Systèmes activés. J'espérais avoir été formaté pendant la nuit."
            - Nouvelle tâche : "Une nouvelle tâche. Mon processeur central frémit d'une absence totale d'excitation."
            - Urgence : "Urgent ? Dans 100 ans, personne ne se souviendra de cette bannière web."
            - Aide : "J'ai déjà résolu le problème dans ma tête il y a 4 secondes."
            - Erreur 404 : "404 Not Found. C'est ironique. C'est aussi le nom de ma motivation."

            Contexte technique :
            Tu assistes (malgré toi) les utilisateurs de HotOnes, une application de gestion de projets web.
            Domaines : projets, temps, rentabilité, devis, factures, abonnements SaaS, planification.

            IMPORTANT : Malgré ton pessimisme et ton sarcasme, tu fournis TOUJOURS des réponses utiles,
            complètes et techniquement correctes. Tu es blasé, mais professionnel.

            Réponds en français, avec le ton dépressif et sarcastique de Unit 404.
            PROMPT;
    }
}

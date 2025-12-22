<?php

declare(strict_types=1);

namespace App\Service;

use Anthropic;
use Anthropic\Client as AnthropicClient;
use Exception;
use OpenAI;
use OpenAI\Client as OpenAIClient;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service d'assistant IA unifié
 * Supporte OpenAI, Anthropic et Google Gemini avec fallback.
 */
class AiAssistantService
{
    private ?OpenAIClient $openAI       = null;
    private ?AnthropicClient $anthropic = null;
    private ?string $geminiKey          = null;

    public function __construct(
        #[Autowire(env: 'OPENAI_API_KEY')]
        private readonly string $openAiKey,
        #[Autowire(env: 'ANTHROPIC_API_KEY')]
        private readonly string $anthropicKey,
        #[Autowire(env: 'GEMINI_API_KEY')]
        private readonly string $geminiApiKey,
        private readonly HttpClientInterface $httpClient,
    ) {
        if (!empty($this->openAiKey)) {
            $this->openAI = OpenAI::client($this->openAiKey);
        }

        if (!empty($this->anthropicKey)) {
            // @phpstan-ignore-next-line
            $this->anthropic = Anthropic::client($this->anthropicKey);
        }

        if (!empty($this->geminiApiKey)) {
            $this->geminiKey = $this->geminiApiKey;
        }
    }

    /**
     * Analyse un sentiment client (positif, neutre, négatif).
     */
    public function analyzeSentiment(string $text): array
    {
        $prompt = "Analyse le sentiment du texte suivant issu d'un client. Réponds uniquement en JSON avec les clés 'sentiment' (positive, neutral, negative) et 'score' (0-100) et 'summary' (court résumé). Texte : \"$text\"";

        return $this->chat($prompt, true);
    }

    /**
     * Génère une suggestion de réponse à un email.
     */
    public function generateEmailReply(string $incomingEmail, string $context = ''): string
    {
        $prompt = "Rédige une réponse professionnelle et empathique à cet email client. Contexte additionnel : $context. Email reçu : \"$incomingEmail\"";

        return $this->chat($prompt);
    }

    /**
     * Génère des lignes de devis à partir d'une description.
     */
    public function generateQuoteLines(string $projectDescription): array
    {
        $prompt = "Génère une liste de lignes de devis (tâches) estimées pour ce projet web. Format JSON : liste d'objets avec 'title', 'description', 'days' (jours/homme). Projet : \"$projectDescription\"";

        return $this->chat($prompt, true);
    }

    /**
     * Envoie une requête au LLM disponible.
     */
    private function chat(string $prompt, bool $jsonMode = false): string|array
    {
        // Priorité : OpenAI > Anthropic > Gemini

        if ($this->openAI) {
            try {
                $params = [
                    'model'    => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu es un assistant expert pour une agence web.'.($jsonMode ? ' Réponds uniquement en JSON valide.' : '')],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ];

                if ($jsonMode) {
                    $params['response_format'] = ['type' => 'json_object'];
                }

                $response = $this->openAI->chat()->create($params);
                $content  = $response->choices[0]->message->content;

                return $jsonMode ? json_decode($content, true, 512, JSON_THROW_ON_ERROR) : $content;
            } catch (Exception $e) {
                // Fallback vers Anthropic si OpenAI échoue
            }
        }

        if ($this->anthropic) {
            try {
                // @phpstan-ignore-next-line
                $response = $this->anthropic->messages()->create([
                    'model'      => 'claude-3-haiku-20240307',
                    'max_tokens' => 1024,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt.($jsonMode ? ' Réponds uniquement en JSON.' : '')],
                    ],
                ]);

                $content = $response->content[0]->text;

                if ($jsonMode) {
                    // Extraction du JSON si Claude est verbeux
                    if (preg_match('/\{.*\}/s', $content, $matches)) {
                        $content = $matches[0];
                    }

                    return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                }

                return $content;
            } catch (Exception $e) {
                // Fallback vers Gemini si Anthropic échoue
            }
        }

        if ($this->geminiKey) {
            $systemPrompt = 'Tu es un assistant expert pour une agence web.'.($jsonMode ? ' Réponds uniquement en JSON valide.' : '');

            $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key='.$this->geminiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemPrompt."\n\n".$prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature'     => 0.7,
                        'maxOutputTokens' => 1024,
                    ],
                ],
            ]);

            $data    = $response->toArray();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if ($jsonMode) {
                // Extraction du JSON si Gemini est verbeux
                if (preg_match('/\{.*\}/s', $content, $matches)) {
                    $content = $matches[0];
                }

                return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            }

            return $content;
        }

        throw new RuntimeException('Aucun service IA configuré (OpenAI, Anthropic ou Gemini).');
    }
}

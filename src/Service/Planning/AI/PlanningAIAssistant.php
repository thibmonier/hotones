<?php

declare(strict_types=1);

namespace App\Service\Planning\AI;

use Anthropic\Client as AnthropicClient;
use Anthropic\Messages\MessageParam;

use function count;

use Exception;
use OpenAI;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Assistant IA pour générer des recommandations avancées de planning.
 *
 * Utilise OpenAI GPT-4, Anthropic Claude ou Google Gemini pour analyser les situations de charge
 * et générer des recommandations intelligentes basées sur le contexte du projet.
 */
class PlanningAIAssistant
{
    private bool $enabled     = false;
    private ?string $provider = null;
    private ?string $apiKey   = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        ?string $openaiApiKey = null,
        ?string $anthropicApiKey = null,
        ?string $geminiApiKey = null
    ) {
        // Déterminer quel provider est disponible (ordre de priorité)
        if ($openaiApiKey !== null && $openaiApiKey !== '') {
            $this->enabled  = true;
            $this->provider = 'openai';
            $this->apiKey   = $openaiApiKey;
        } elseif ($anthropicApiKey !== null && $anthropicApiKey !== '') {
            $this->enabled  = true;
            $this->provider = 'anthropic';
            $this->apiKey   = $anthropicApiKey;
        } elseif ($geminiApiKey !== null && $geminiApiKey !== '') {
            $this->enabled  = true;
            $this->provider = 'gemini';
            $this->apiKey   = $geminiApiKey;
        }
    }

    /**
     * Vérifie si l'IA est activée et disponible.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Génère des recommandations enrichies par l'IA.
     *
     * @param array $context Contexte incluant l'analyse TACE, les projets, les contributeurs
     *
     * @return array Recommandations enrichies
     */
    public function enhanceRecommendations(array $context): array
    {
        if (!$this->enabled) {
            return [
                'enhanced' => false,
                'message'  => 'AI assistance is not enabled. Set OPENAI_API_KEY, ANTHROPIC_API_KEY or GEMINI_API_KEY in .env to enable.',
            ];
        }

        // Préparer le prompt pour l'IA
        $prompt = $this->buildPrompt($context);

        try {
            // TODO: Implémenter l'appel à l'API IA selon le provider
            $aiResponse = $this->callAI($prompt);

            return [
                'enhanced'        => true,
                'provider'        => $this->provider,
                'recommendations' => $aiResponse['recommendations'] ?? [],
                'insights'        => $aiResponse['insights']        ?? [],
                'confidence'      => $aiResponse['confidence']      ?? 0,
            ];
        } catch (Exception $e) {
            return [
                'enhanced' => false,
                'error'    => $e->getMessage(),
            ];
        }
    }

    /**
     * Construit le prompt pour l'IA.
     */
    private function buildPrompt(array $context): string
    {
        $analysis = $context['analysis'] ?? [];
        $projects = $context['projects'] ?? [];

        $prompt = "Tu es un expert en gestion de projet et optimisation du planning.\n\n";
        $prompt .= "Analyse la situation suivante et propose des recommandations d'optimisation:\n\n";

        $prompt .= "## Contexte\n";
        $prompt .= sprintf(
            "- %d contributeurs en surcharge\n",
            count($analysis['overloaded'] ?? []) + count($analysis['critical'] ?? []),
        );
        $prompt .= sprintf("- %d contributeurs sous-utilisés\n", count($analysis['underutilized'] ?? []));
        $prompt .= sprintf("- %d projets actifs\n", count($projects));

        $prompt .= "\n## Contributeurs en surcharge critique:\n";
        foreach (($analysis['critical'] ?? []) as $item) {
            if ($item['status'] === 'critical_high') {
                $prompt .= sprintf(
                    "- %s (TACE: %.1f%%, Écart: %+.1f points)\n",
                    $item['contributor']->getFullName(),
                    $item['tace'],
                    $item['deviation'],
                );
            }
        }

        $prompt .= "\n## Contributeurs sous-utilisés:\n";
        foreach (($analysis['underutilized'] ?? []) as $item) {
            $prompt .= sprintf(
                "- %s (TACE: %.1f%%, Disponible: %.1f%%)\n",
                $item['contributor']->getFullName(),
                $item['tace'],
                100 - $item['tace'],
            );
        }

        $prompt .= "\n## Mission\n";
        $prompt .= "Propose 3-5 recommandations concrètes et actionnables pour:\n";
        $prompt .= "1. Réduire la surcharge des contributeurs critiques\n";
        $prompt .= "2. Mieux utiliser les contributeurs sous-utilisés\n";
        $prompt .= "3. Optimiser la répartition globale en tenant compte des compétences\n";
        $prompt .= "4. Prioriser les projets clients VIP/Prioritaires\n\n";
        $prompt .= "Format de réponse attendu (JSON):\n";
        $prompt .= "{\n";
        $prompt .= "  \"recommendations\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"title\": \"Titre court\",\n";
        $prompt .= "      \"description\": \"Description détaillée\",\n";
        $prompt .= "      \"impact\": \"Impact estimé\",\n";
        $prompt .= "      \"priority\": \"high/medium/low\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ],\n";
        $prompt .= "  \"insights\": [\"Observation 1\", \"Observation 2\"],\n";
        $prompt .= "  \"confidence\": 0.85\n";
        $prompt .= "}\n";

        return $prompt;
    }

    /**
     * Appelle l'API IA selon le provider configuré.
     */
    private function callAI(string $prompt): array
    {
        if ($this->provider === 'openai') {
            return $this->callOpenAI($prompt);
        }

        if ($this->provider === 'anthropic') {
            return $this->callAnthropic($prompt);
        }

        if ($this->provider === 'gemini') {
            return $this->callGemini($prompt);
        }

        throw new RuntimeException('No AI provider configured.');
    }

    /**
     * Appelle l'API OpenAI pour générer des recommandations.
     */
    private function callOpenAI(string $prompt): array
    {
        $client = OpenAI::client($this->apiKey);

        $response = $client->chat()->create([
            'model'    => 'gpt-4o-mini', // Modèle plus rapide et moins cher que gpt-4
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert in project management and resource planning. You provide actionable recommendations to optimize team workload and project staffing. Always respond in valid JSON format.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens'  => 2000,
        ]);

        $content = $response->choices[0]->message->content;

        // Parser la réponse JSON
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to parse AI response as JSON: '.json_last_error_msg());
        }

        return $data;
    }

    /**
     * Appelle l'API Anthropic Claude pour générer des recommandations.
     */
    private function callAnthropic(string $prompt): array
    {
        $client = new AnthropicClient($this->apiKey);

        $response = $client->messages->create(
            model: 'claude-3-5-haiku-20241022', // Modèle rapide et économique
            maxTokens: 2000,
            messages: [
                MessageParam::with(
                    role: 'user',
                    content: $prompt,
                ),
            ],
            system: 'You are an expert in project management and resource planning. You provide actionable recommendations to optimize team workload and project staffing. Always respond in valid JSON format.',
        );

        // Extraire le contenu textuel de la réponse
        $content = '';
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $content .= $block->text;
            }
        }

        // Parser la réponse JSON
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to parse AI response as JSON: '.json_last_error_msg());
        }

        return $data;
    }

    /**
     * Appelle l'API Google Gemini pour générer des recommandations.
     */
    private function callGemini(string $prompt): array
    {
        $systemPrompt = 'You are an expert in project management and resource planning. You provide actionable recommendations to optimize team workload and project staffing. Always respond in valid JSON format.';

        $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key='.$this->apiKey, [
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
                    'maxOutputTokens' => 2000,
                ],
            ],
        ]);

        $data = $response->toArray();

        // Extraire le contenu textuel de la réponse Gemini
        $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($content)) {
            throw new RuntimeException('Empty response from Gemini API');
        }

        // Parser la réponse JSON
        $parsedData = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to parse AI response as JSON: '.json_last_error_msg());
        }

        return $parsedData;
    }

    /**
     * Analyse de texte libre pour extraire des insights.
     */
    public function analyzeText(string $text): array
    {
        if (!$this->enabled) {
            return ['enabled' => false];
        }

        // TODO: Implémenter l'analyse de texte via IA
        // Utile pour analyser des notes de projet, feedbacks, etc.

        return ['enabled' => false, 'message' => 'Not yet implemented'];
    }
}

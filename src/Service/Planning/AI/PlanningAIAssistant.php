<?php

declare(strict_types=1);

namespace App\Service\Planning\AI;

use function count;

use Exception;
use RuntimeException;

/**
 * Assistant IA pour générer des recommandations avancées de planning.
 *
 * Cette classe sert d'interface pour intégrer différents services d'IA
 * (OpenAI, Claude, modèles locaux, etc.) pour améliorer les recommandations.
 *
 * Pour activer l'IA :
 * 1. Configurer une clé API dans .env (ex: OPENAI_API_KEY)
 * 2. Installer le client correspondant (ex: composer require openai-php/client)
 * 3. Implémenter la méthode generateRecommendations() avec l'API choisie
 */
class PlanningAIAssistant
{
    private bool $enabled     = false;
    private ?string $provider = null;

    public function __construct(
        ?string $openaiApiKey = null,
        ?string $anthropicApiKey = null
    ) {
        // Déterminer quel provider est disponible
        if ($openaiApiKey) {
            $this->enabled  = true;
            $this->provider = 'openai';
        } elseif ($anthropicApiKey) {
            $this->enabled  = true;
            $this->provider = 'anthropic';
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
                'message'  => 'AI assistance is not enabled. Set OPENAI_API_KEY or ANTHROPIC_API_KEY in .env to enable.',
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
     * Appelle l'API IA (à implémenter selon le provider).
     */
    private function callAI(string $prompt): array
    {
        // TODO: Implémenter selon le provider
        // Exemple pour OpenAI:
        /*
        if ($this->provider === 'openai') {
            $client = OpenAI::client($this->apiKey);
            $response = $client->chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a project planning expert.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
            return json_decode($response->choices[0]->message->content, true);
        }
        */

        // Pour l'instant, retourner un message indiquant que l'implémentation est nécessaire
        throw new RuntimeException('AI integration not yet implemented. To enable: 1) Install openai-php/client or anthropic-sdk 2) Implement the callAI() method in PlanningAIAssistant');
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

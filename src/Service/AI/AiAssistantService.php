<?php

declare(strict_types=1);

namespace App\Service\AI;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service d'assistant IA unifié utilisant Symfony AI Bundle.
 *
 * Remplace l'ancien AiAssistantService avec une architecture simplifiée
 * basée sur les agents Symfony AI.
 */
final readonly class AiAssistantService
{
    public function __construct(
        #[Autowire(service: 'ai.agent.sentiment_analyzer')]
        private AgentInterface $sentimentAgent,

        #[Autowire(service: 'ai.agent.email_responder')]
        private AgentInterface $emailAgent,

        #[Autowire(service: 'ai.agent.quote_generator')]
        private AgentInterface $quoteAgent,
    ) {
    }

    /**
     * Analyse un sentiment client (positif, neutre, négatif).
     *
     * @return array{sentiment: string, score: int, summary: string}
     */
    public function analyzeSentiment(string $text): array
    {
        $messages = new MessageBag(
            Message::ofUser("Texte : \"{$text}\""),
        );

        $response = $this->sentimentAgent->call($messages);

        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Génère une suggestion de réponse à un email.
     */
    public function generateEmailReply(string $incomingEmail, string $context = ''): string
    {
        $prompt = $context
            ? "Contexte additionnel : {$context}\n\nEmail reçu : \"{$incomingEmail}\""
            : "Email reçu : \"{$incomingEmail}\"";

        $messages = new MessageBag(
            Message::ofUser($prompt),
        );

        return (string) $this->emailAgent->call($messages);
    }

    /**
     * Génère des lignes de devis à partir d'une description.
     *
     * @return array<int, array{title: string, description: string, days: float}>
     */
    public function generateQuoteLines(string $projectDescription): array
    {
        $messages = new MessageBag(
            Message::ofUser("Projet : \"{$projectDescription}\""),
        );

        $response = $this->quoteAgent->call($messages);

        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }
}

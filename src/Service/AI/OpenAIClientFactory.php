<?php

declare(strict_types=1);

namespace App\Service\AI;

use GuzzleHttp\Client as GuzzleClient;
use OpenAI;
use OpenAI\Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Factory for creating OpenAI API clients.
 */
class OpenAIClientFactory
{
    public function __construct(
        #[Autowire(param: 'env(OPENAI_API_KEY)')]
        private readonly string $openaiApiKey,
        private readonly string $baseUri = 'https://api.openai.com/v1'
    ) {
    }

    public function createClient(): Client
    {
        return OpenAI::factory()
            ->withApiKey($this->openaiApiKey)
            ->withBaseUri($this->baseUri)
            ->withHttpClient(new GuzzleClient([
                'timeout'         => 60,
                'connect_timeout' => 10,
            ]))
            ->make();
    }
}

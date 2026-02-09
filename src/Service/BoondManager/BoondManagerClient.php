<?php

declare(strict_types=1);

namespace App\Service\BoondManager;

use App\Entity\BoondManagerSettings;
use DateTimeInterface;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Client HTTP pour l'API BoondManager.
 * Gere l'authentification et les appels aux differents endpoints.
 */
class BoondManagerClient
{
    private const TIMEOUT              = 30;
    private const MAX_RESULTS_PER_PAGE = 100;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Teste la connexion a l'API BoondManager.
     */
    public function testConnection(BoondManagerSettings $settings): bool
    {
        try {
            $response = $this->request($settings, 'GET', '/api/application/dictionary');

            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            $this->logger->warning('BoondManager connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Recupere les temps passes (timesheets) depuis BoondManager.
     *
     * @param DateTimeInterface $startDate Date de debut
     * @param DateTimeInterface $endDate   Date de fin
     *
     * @return array<int, array<string, mixed>> Liste des temps passes
     */
    public function getTimes(
        BoondManagerSettings $settings,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
    ): array {
        $allTimes = [];
        $page     = 1;

        do {
            $response = $this->request($settings, 'GET', '/api/times', [
                'query' => [
                    'startDate'  => $startDate->format('Y-m-d'),
                    'endDate'    => $endDate->format('Y-m-d'),
                    'maxResults' => self::MAX_RESULTS_PER_PAGE,
                    'page'       => $page,
                ],
            ]);

            $data     = $response->toArray();
            $times    = $data['data'] ?? [];
            $allTimes = array_merge($allTimes, $times);

            $hasMore = isset($data['meta']['pagination']['hasMore']) && $data['meta']['pagination']['hasMore'];
            ++$page;
        } while ($hasMore && $page < 100); // Safety limit

        $this->logger->info('BoondManager: Retrieved {count} time entries', [
            'count'     => count($allTimes),
            'startDate' => $startDate->format('Y-m-d'),
            'endDate'   => $endDate->format('Y-m-d'),
        ]);

        return $allTimes;
    }

    /**
     * Recupere les ressources (collaborateurs) depuis BoondManager.
     *
     * @return array<int, array<string, mixed>> Liste des ressources
     */
    public function getResources(BoondManagerSettings $settings): array
    {
        $allResources = [];
        $page         = 1;

        do {
            $response = $this->request($settings, 'GET', '/api/resources', [
                'query' => [
                    'maxResults' => self::MAX_RESULTS_PER_PAGE,
                    'page'       => $page,
                ],
            ]);

            $data         = $response->toArray();
            $resources    = $data['data'] ?? [];
            $allResources = array_merge($allResources, $resources);

            $hasMore = isset($data['meta']['pagination']['hasMore']) && $data['meta']['pagination']['hasMore'];
            ++$page;
        } while ($hasMore && $page < 100);

        $this->logger->info('BoondManager: Retrieved {count} resources', [
            'count' => count($allResources),
        ]);

        return $allResources;
    }

    /**
     * Recupere une ressource specifique par son ID.
     *
     * @return array<string, mixed>|null
     */
    public function getResource(BoondManagerSettings $settings, int $resourceId): ?array
    {
        try {
            $response = $this->request($settings, 'GET', '/api/resources/'.$resourceId);
            $data     = $response->toArray();

            return $data['data'] ?? null;
        } catch (Exception $e) {
            $this->logger->warning('BoondManager: Failed to get resource', [
                'resourceId' => $resourceId,
                'error'      => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Recupere les projets (missions) depuis BoondManager.
     *
     * @return array<int, array<string, mixed>> Liste des projets
     */
    public function getProjects(BoondManagerSettings $settings): array
    {
        $allProjects = [];
        $page        = 1;

        do {
            $response = $this->request($settings, 'GET', '/api/projects', [
                'query' => [
                    'maxResults' => self::MAX_RESULTS_PER_PAGE,
                    'page'       => $page,
                ],
            ]);

            $data        = $response->toArray();
            $projects    = $data['data'] ?? [];
            $allProjects = array_merge($allProjects, $projects);

            $hasMore = isset($data['meta']['pagination']['hasMore']) && $data['meta']['pagination']['hasMore'];
            ++$page;
        } while ($hasMore && $page < 100);

        $this->logger->info('BoondManager: Retrieved {count} projects', [
            'count' => count($allProjects),
        ]);

        return $allProjects;
    }

    /**
     * Recupere un projet specifique par son ID.
     *
     * @return array<string, mixed>|null
     */
    public function getProject(BoondManagerSettings $settings, int $projectId): ?array
    {
        try {
            $response = $this->request($settings, 'GET', '/api/projects/'.$projectId);
            $data     = $response->toArray();

            return $data['data'] ?? null;
        } catch (Exception $e) {
            $this->logger->warning('BoondManager: Failed to get project', [
                'projectId' => $projectId,
                'error'     => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Recupere le dictionnaire de l'application (types, statuts, etc.).
     *
     * @return array<string, mixed>|null
     */
    public function getDictionary(BoondManagerSettings $settings): ?array
    {
        try {
            $response = $this->request($settings, 'GET', '/api/application/dictionary');
            $data     = $response->toArray();

            return $data['data'] ?? null;
        } catch (Exception $e) {
            $this->logger->warning('BoondManager: Failed to get dictionary', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Effectue une requete HTTP vers l'API BoondManager.
     *
     * @param array<string, mixed> $options Options supplementaires pour la requete
     */
    private function request(
        BoondManagerSettings $settings,
        string $method,
        string $endpoint,
        array $options = [],
    ): ResponseInterface {
        $baseUrl = rtrim($settings->apiBaseUrl ?? '', '/');
        $url     = $baseUrl.$endpoint;

        $defaultOptions = [
            'timeout' => self::TIMEOUT,
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        // Configuration de l'authentification
        if ($settings->authType === 'basic') {
            $defaultOptions['auth_basic'] = [
                $settings->apiUsername ?? '',
                $settings->apiPassword ?? '',
            ];
        } else {
            // JWT Authentication
            $jwtToken                                   = $this->generateJwtToken($settings);
            $defaultOptions['headers']['Authorization'] = 'Bearer '.$jwtToken;
        }

        $mergedOptions = array_merge_recursive($defaultOptions, $options);

        $this->logger->debug('BoondManager API request', [
            'method' => $method,
            'url'    => $url,
        ]);

        return $this->httpClient->request($method, $url, $mergedOptions);
    }

    /**
     * Genere un token JWT pour l'authentification.
     */
    private function generateJwtToken(BoondManagerSettings $settings): string
    {
        // Le JWT BoondManager est construit avec: userToken + clientToken + clientKey
        // Format: base64(userToken).base64(clientToken).signature(clientKey)
        $header  = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'userToken'   => $settings->userToken,
            'clientToken' => $settings->clientToken,
            'iat'         => time(),
            'exp'         => time() + 3600,
        ]);

        if ($header === false || $payload === false) {
            throw new RuntimeException('Failed to encode JWT components');
        }

        $base64Header  = $this->base64UrlEncode($header);
        $base64Payload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64Header.'.'.$base64Payload, $settings->clientKey ?? '', true);

        return $base64Header.'.'.$base64Payload.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

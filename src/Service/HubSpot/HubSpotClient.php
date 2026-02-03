<?php

declare(strict_types=1);

namespace App\Service\HubSpot;

use App\Entity\HubSpotSettings;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Client HTTP pour l'API HubSpot v3.
 * Gere l'authentification et les appels aux differents endpoints CRM.
 */
class HubSpotClient
{
    private const API_BASE_URL = 'https://api.hubapi.com';
    private const TIMEOUT = 30;
    private const MAX_RESULTS_PER_PAGE = 100;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Teste la connexion a l'API HubSpot.
     */
    public function testConnection(HubSpotSettings $settings): bool
    {
        try {
            $response = $this->request($settings, 'GET', '/crm/v3/objects/contacts', [
                'query' => ['limit' => 1],
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->warning('HubSpot connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Recupere les informations du compte HubSpot.
     *
     * @return array<string, mixed>|null
     */
    public function getAccountInfo(HubSpotSettings $settings): ?array
    {
        try {
            $response = $this->request($settings, 'GET', '/account-info/v3/details');

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->warning('HubSpot: Failed to get account info', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Recupere les pipelines de deals disponibles.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDealPipelines(HubSpotSettings $settings): array
    {
        try {
            $response = $this->request($settings, 'GET', '/crm/v3/pipelines/deals');
            $data = $response->toArray();

            $this->logger->info('HubSpot: Retrieved {count} deal pipelines', [
                'count' => count($data['results'] ?? []),
            ]);

            return $data['results'] ?? [];
        } catch (\Exception $e) {
            $this->logger->warning('HubSpot: Failed to get deal pipelines', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Recupere les deals (affaires) depuis HubSpot.
     *
     * @param string[] $excludedStages Stages a exclure (ex: closedwon, closedlost)
     * @param string[] $pipelineIds    Pipeline IDs a filtrer (vide = tous)
     *
     * @return array<int, array<string, mixed>> Liste des deals
     */
    public function getDeals(
        HubSpotSettings $settings,
        array $excludedStages = [],
        array $pipelineIds = [],
    ): array {
        $allDeals = [];
        $after = null;
        $properties = [
            'dealname',
            'amount',
            'dealstage',
            'pipeline',
            'closedate',
            'createdate',
            'hs_lastmodifieddate',
            'hubspot_owner_id',
            'notes_last_updated',
        ];

        do {
            $queryParams = [
                'limit' => self::MAX_RESULTS_PER_PAGE,
                'properties' => implode(',', $properties),
                'associations' => 'companies,contacts',
            ];

            if ($after !== null) {
                $queryParams['after'] = $after;
            }

            $response = $this->request($settings, 'GET', '/crm/v3/objects/deals', [
                'query' => $queryParams,
            ]);

            $data = $response->toArray();
            $deals = $data['results'] ?? [];

            // Filtrer les deals selon les criteres
            foreach ($deals as $deal) {
                $stage = $deal['properties']['dealstage'] ?? '';
                $pipeline = $deal['properties']['pipeline'] ?? '';

                // Exclure les stages non desires
                if (in_array($stage, $excludedStages, true)) {
                    continue;
                }

                // Filtrer par pipeline si specifie
                if (!empty($pipelineIds) && !in_array($pipeline, $pipelineIds, true)) {
                    continue;
                }

                $allDeals[] = $deal;
            }

            $after = $data['paging']['next']['after'] ?? null;
        } while ($after !== null && count($allDeals) < 10000); // Safety limit

        $this->logger->info('HubSpot: Retrieved {count} deals', [
            'count' => count($allDeals),
            'excludedStages' => $excludedStages,
            'pipelineIds' => $pipelineIds,
        ]);

        return $allDeals;
    }

    /**
     * Recupere un deal specifique par son ID avec ses associations.
     *
     * @return array<string, mixed>|null
     */
    public function getDeal(HubSpotSettings $settings, string $dealId): ?array
    {
        try {
            $properties = [
                'dealname',
                'amount',
                'dealstage',
                'pipeline',
                'closedate',
                'createdate',
                'hs_lastmodifieddate',
                'hubspot_owner_id',
                'description',
            ];

            $response = $this->request($settings, 'GET', '/crm/v3/objects/deals/' . $dealId, [
                'query' => [
                    'properties' => implode(',', $properties),
                    'associations' => 'companies,contacts',
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->warning('HubSpot: Failed to get deal', [
                'dealId' => $dealId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Recupere les companies (clients) depuis HubSpot.
     *
     * @return array<int, array<string, mixed>> Liste des companies
     */
    public function getCompanies(HubSpotSettings $settings): array
    {
        $allCompanies = [];
        $after = null;
        $properties = [
            'name',
            'domain',
            'description',
            'phone',
            'website',
            'industry',
            'city',
            'country',
            'createdate',
            'hs_lastmodifieddate',
        ];

        do {
            $queryParams = [
                'limit' => self::MAX_RESULTS_PER_PAGE,
                'properties' => implode(',', $properties),
                'associations' => 'contacts,deals',
            ];

            if ($after !== null) {
                $queryParams['after'] = $after;
            }

            $response = $this->request($settings, 'GET', '/crm/v3/objects/companies', [
                'query' => $queryParams,
            ]);

            $data = $response->toArray();
            $companies = $data['results'] ?? [];
            $allCompanies = array_merge($allCompanies, $companies);

            $after = $data['paging']['next']['after'] ?? null;
        } while ($after !== null && count($allCompanies) < 10000);

        $this->logger->info('HubSpot: Retrieved {count} companies', [
            'count' => count($allCompanies),
        ]);

        return $allCompanies;
    }

    /**
     * Recupere une company specifique par son ID.
     *
     * @return array<string, mixed>|null
     */
    public function getCompany(HubSpotSettings $settings, string $companyId): ?array
    {
        try {
            $properties = [
                'name',
                'domain',
                'description',
                'phone',
                'website',
                'industry',
                'city',
                'country',
            ];

            $response = $this->request($settings, 'GET', '/crm/v3/objects/companies/' . $companyId, [
                'query' => [
                    'properties' => implode(',', $properties),
                    'associations' => 'contacts,deals',
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->warning('HubSpot: Failed to get company', [
                'companyId' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Recupere les contacts depuis HubSpot.
     *
     * @return array<int, array<string, mixed>> Liste des contacts
     */
    public function getContacts(HubSpotSettings $settings): array
    {
        $allContacts = [];
        $after = null;
        $properties = [
            'firstname',
            'lastname',
            'email',
            'phone',
            'mobilephone',
            'jobtitle',
            'company',
            'createdate',
            'hs_lastmodifieddate',
        ];

        do {
            $queryParams = [
                'limit' => self::MAX_RESULTS_PER_PAGE,
                'properties' => implode(',', $properties),
                'associations' => 'companies',
            ];

            if ($after !== null) {
                $queryParams['after'] = $after;
            }

            $response = $this->request($settings, 'GET', '/crm/v3/objects/contacts', [
                'query' => $queryParams,
            ]);

            $data = $response->toArray();
            $contacts = $data['results'] ?? [];
            $allContacts = array_merge($allContacts, $contacts);

            $after = $data['paging']['next']['after'] ?? null;
        } while ($after !== null && count($allContacts) < 10000);

        $this->logger->info('HubSpot: Retrieved {count} contacts', [
            'count' => count($allContacts),
        ]);

        return $allContacts;
    }

    /**
     * Recupere un contact specifique par son ID.
     *
     * @return array<string, mixed>|null
     */
    public function getContact(HubSpotSettings $settings, string $contactId): ?array
    {
        try {
            $properties = [
                'firstname',
                'lastname',
                'email',
                'phone',
                'mobilephone',
                'jobtitle',
                'company',
            ];

            $response = $this->request($settings, 'GET', '/crm/v3/objects/contacts/' . $contactId, [
                'query' => [
                    'properties' => implode(',', $properties),
                    'associations' => 'companies',
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->warning('HubSpot: Failed to get contact', [
                'contactId' => $contactId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Recupere les contacts associes a une company.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getContactsByCompany(HubSpotSettings $settings, string $companyId): array
    {
        try {
            $response = $this->request(
                $settings,
                'GET',
                '/crm/v3/objects/companies/' . $companyId . '/associations/contacts',
            );

            $data = $response->toArray();
            $contactIds = array_map(
                fn (array $assoc) => $assoc['id'],
                $data['results'] ?? [],
            );

            if (empty($contactIds)) {
                return [];
            }

            // Recuperer les details des contacts
            $contacts = [];
            foreach ($contactIds as $contactId) {
                $contact = $this->getContact($settings, $contactId);
                if ($contact !== null) {
                    $contacts[] = $contact;
                }
            }

            return $contacts;
        } catch (\Exception $e) {
            $this->logger->warning('HubSpot: Failed to get contacts by company', [
                'companyId' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Recherche des deals avec des criteres specifiques.
     *
     * @param array<string, mixed> $filters Filtres de recherche
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchDeals(HubSpotSettings $settings, array $filters = []): array
    {
        try {
            $properties = [
                'dealname',
                'amount',
                'dealstage',
                'pipeline',
                'closedate',
                'createdate',
            ];

            $body = [
                'properties' => $properties,
                'limit' => self::MAX_RESULTS_PER_PAGE,
            ];

            if (!empty($filters)) {
                $body['filterGroups'] = [
                    [
                        'filters' => $filters,
                    ],
                ];
            }

            $response = $this->request($settings, 'POST', '/crm/v3/objects/deals/search', [
                'json' => $body,
            ]);

            $data = $response->toArray();

            return $data['results'] ?? [];
        } catch (\Exception $e) {
            $this->logger->warning('HubSpot: Failed to search deals', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Effectue une requete HTTP vers l'API HubSpot.
     *
     * @param array<string, mixed> $options Options supplementaires pour la requete
     */
    private function request(
        HubSpotSettings $settings,
        string $method,
        string $endpoint,
        array $options = [],
    ): ResponseInterface {
        $url = self::API_BASE_URL . $endpoint;

        $defaultOptions = [
            'timeout' => self::TIMEOUT,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $settings->accessToken,
            ],
        ];

        $mergedOptions = array_merge_recursive($defaultOptions, $options);

        $this->logger->debug('HubSpot API request', [
            'method' => $method,
            'url' => $url,
        ]);

        return $this->httpClient->request($method, $url, $mergedOptions);
    }
}

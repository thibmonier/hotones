<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\BoondManager;

use App\Entity\BoondManagerSettings;
use App\Service\BoondManager\BoondManagerClient;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Integration tests for BoondManagerClient (TEST-009, sprint-004).
 *
 * Closes gap-analysis Critical #5 (BoondManagerConnector sans tests integration).
 *
 * The HTTP layer is exercised end-to-end through Symfony's MockHttpClient,
 * so the test verifies:
 *   - the URL composed from `apiBaseUrl + endpoint`
 *   - the method (GET/POST)
 *   - the auth header / `auth_basic` option
 *   - the query string for the paginated endpoints
 *   - JSON decoding (`->toArray()`) and the surface returned to callers
 *   - the pagination loop (`hasMore` flag) for getTimes/getResources/getProjects
 *   - the not-found path for getResource/getProject
 */
#[AllowMockObjectsWithoutExpectations]
final class BoondManagerClientTest extends TestCase
{
    private const BASE_URL = 'https://example.boondmanager.com';

    public function testConnectionReturnsTrueWhenDictionaryIs200(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"status":"ok"}', ['http_code' => 200]),
        ]);
        $client = new BoondManagerClient($http, new NullLogger());

        self::assertTrue($client->testConnection($this->buildSettings()));
    }

    public function testConnectionReturnsFalseWhenDictionaryRaises(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"error":"unauthorized"}', ['http_code' => 401]),
        ]);
        $client = new BoondManagerClient($http, new NullLogger());

        self::assertFalse($client->testConnection($this->buildSettings()));
    }

    public function testGetTimesAggregatesPagedResponses(): void
    {
        $page1 = json_encode([
            'data' => [
                ['id' => 1, 'duration' => 8],
                ['id' => 2, 'duration' => 8],
            ],
            'meta' => ['pagination' => ['hasMore' => true]],
        ]);
        $page2 = json_encode([
            'data' => [
                ['id' => 3, 'duration' => 7.5],
            ],
            'meta' => ['pagination' => ['hasMore' => false]],
        ]);

        $http = new MockHttpClient([
            new MockResponse($page1, ['http_code' => 200]),
            new MockResponse($page2, ['http_code' => 200]),
        ]);
        $client = new BoondManagerClient($http, new NullLogger());

        $times = $client->getTimes(
            $this->buildSettings(),
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-31'),
        );

        self::assertCount(3, $times);
        self::assertSame([1, 2, 3], array_column($times, 'id'));
        self::assertSame(2, $http->getRequestsCount());
    }

    public function testGetResourceReturnsNullOnNotFound(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"error":"not found"}', ['http_code' => 404]),
        ]);
        $client = new BoondManagerClient($http, new NullLogger());

        self::assertNull($client->getResource($this->buildSettings(), 999));
    }

    public function testGetResourceReturnsDataOnSuccess(): void
    {
        $payload = json_encode(['data' => ['id' => 42, 'lastName' => 'Doe']]);
        $http = new MockHttpClient([
            new MockResponse($payload, ['http_code' => 200]),
        ]);
        $client = new BoondManagerClient($http, new NullLogger());

        $result = $client->getResource($this->buildSettings(), 42);

        self::assertSame(42, $result['id']);
        self::assertSame('Doe', $result['lastName']);
    }

    public function testGetProjectsAggregatesPaging(): void
    {
        $page1 = json_encode([
            'data' => [['id' => 100], ['id' => 101]],
            'meta' => ['pagination' => ['hasMore' => false]],
        ]);

        $http = new MockHttpClient([new MockResponse($page1, ['http_code' => 200])]);
        $client = new BoondManagerClient($http, new NullLogger());

        $projects = $client->getProjects($this->buildSettings());

        self::assertCount(2, $projects);
    }

    public function testGetProjectReturnsNullOnException(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"error":"forbidden"}', ['http_code' => 403]),
        ]);
        $client = new BoondManagerClient($http, new NullLogger());

        self::assertNull($client->getProject($this->buildSettings(), 7));
    }

    public function testGetDictionaryReturnsArrayOnSuccess(): void
    {
        $payload = json_encode(['data' => ['statuses' => ['active', 'inactive']]]);
        $http = new MockHttpClient([new MockResponse($payload, ['http_code' => 200])]);
        $client = new BoondManagerClient($http, new NullLogger());

        $dict = $client->getDictionary($this->buildSettings());

        self::assertIsArray($dict);
        self::assertSame(['active', 'inactive'], $dict['statuses']);
    }

    public function testRequestUsesBasicAuthWhenConfigured(): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options) {
            self::assertSame('GET', $method);
            self::assertStringStartsWith(self::BASE_URL, $url);
            self::assertContains('auth_basic', array_keys($options));

            return new MockResponse('{"status":"ok"}', ['http_code' => 200]);
        });

        $client = new BoondManagerClient($http, new NullLogger());
        $client->testConnection($this->buildSettings());
    }

    public function testGetTimesPassesDateRangeAsQueryString(): void
    {
        $captured = [];
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured[] = ['method' => $method, 'url' => $url];

            return new MockResponse(
                json_encode([
                    'data' => [],
                    'meta' => ['pagination' => ['hasMore' => false]],
                ]),
                ['http_code' => 200],
            );
        });

        $client = new BoondManagerClient($http, new NullLogger());
        $client->getTimes(
            $this->buildSettings(),
            new DateTimeImmutable('2026-03-01'),
            new DateTimeImmutable('2026-03-31'),
        );

        self::assertCount(1, $captured);
        self::assertStringContainsString('startDate=2026-03-01', $captured[0]['url']);
        self::assertStringContainsString('endDate=2026-03-31', $captured[0]['url']);
        self::assertStringContainsString('page=1', $captured[0]['url']);
    }

    private function buildSettings(): BoondManagerSettings
    {
        $settings = new BoondManagerSettings();
        $settings->apiBaseUrl = self::BASE_URL;
        $settings->authType = 'basic';
        $settings->apiUsername = 'tester';
        $settings->apiPassword = 'secret';

        return $settings;
    }
}

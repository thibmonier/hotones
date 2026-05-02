<?php

declare(strict_types=1);

namespace App\Tests\Contract;

use App\Entity\BoondManagerSettings;
use App\Service\BoondManager\BoondManagerClient;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;

/**
 * TEST-CONNECTORS-CONTRACT-001 — Contract test against the real BoondManager
 * sandbox API.
 *
 * Hits the live sandbox endpoints to verify that the BoondManagerClient still
 * speaks the API correctly (response shapes, auth headers, status codes).
 *
 * Skipped when sandbox credentials are not provided. Configure on the repo via
 * Settings → Secrets → Actions:
 *   - BOOND_SANDBOX_BASE_URL
 *   - BOOND_SANDBOX_USERNAME
 *   - BOOND_SANDBOX_PASSWORD
 *
 * Default phpunit.xml.dist excludes the "contract" group, so this only runs
 * via `composer test-contract` (phpunit-contract.xml.dist) or the weekly cron
 * GitHub Actions workflow.
 */
#[Group('contract')]
final class BoondManagerContractTest extends TestCase
{
    private BoondManagerSettings $settings;

    protected function setUp(): void
    {
        $baseUrl = $_SERVER['BOOND_SANDBOX_BASE_URL'] ?? $_ENV['BOOND_SANDBOX_BASE_URL'] ?? null;
        $username = $_SERVER['BOOND_SANDBOX_USERNAME'] ?? $_ENV['BOOND_SANDBOX_USERNAME'] ?? null;
        $password = $_SERVER['BOOND_SANDBOX_PASSWORD'] ?? $_ENV['BOOND_SANDBOX_PASSWORD'] ?? null;

        if (!$baseUrl || !$username || !$password) {
            self::markTestSkipped(
                'BoondManager sandbox credentials not provided '
                .'(set BOOND_SANDBOX_BASE_URL / BOOND_SANDBOX_USERNAME / BOOND_SANDBOX_PASSWORD).',
            );
        }

        $this->settings = new BoondManagerSettings();
        $this->settings->apiBaseUrl = $baseUrl;
        $this->settings->authType = 'basic';
        $this->settings->apiUsername = $username;
        $this->settings->apiPassword = $password;
    }

    public function testConnectionAgainstRealSandbox(): void
    {
        $client = new BoondManagerClient(HttpClient::create(), new NullLogger());

        self::assertTrue(
            $client->testConnection($this->settings),
            'BoondManager sandbox /api/application/dictionary did not return 200.',
        );
    }

    public function testGetDictionaryReturnsKnownShape(): void
    {
        $client = new BoondManagerClient(HttpClient::create(), new NullLogger());

        $dictionary = $client->getDictionary($this->settings);

        self::assertNotNull($dictionary, 'Dictionary call returned null.');
        self::assertIsArray($dictionary);
        self::assertNotEmpty(
            $dictionary,
            'Dictionary array is empty — Boond may have changed the response shape.',
        );
    }

    public function testGetTimesAcceptsDateRangeWithoutCrashing(): void
    {
        $client = new BoondManagerClient(HttpClient::create(), new NullLogger());

        $endDate = new DateTimeImmutable('today');
        $startDate = $endDate->modify('-7 days');

        $times = $client->getTimes($this->settings, $startDate, $endDate);

        self::assertIsArray($times, 'getTimes() must return an array (even when empty).');
    }
}

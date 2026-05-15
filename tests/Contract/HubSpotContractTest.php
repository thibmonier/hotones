<?php

declare(strict_types=1);

namespace App\Tests\Contract;

use App\Entity\HubSpotSettings;
use App\Service\HubSpot\HubSpotClient;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;

/**
 * TEST-CONNECTORS-CONTRACT-001 — Contract test against the real HubSpot
 * sandbox API.
 *
 * Hits the live HubSpot Developer sandbox to verify that HubSpotClient still
 * speaks the v3 CRM API correctly.
 *
 * Skipped when sandbox token is not provided. Configure on the repo via
 * Settings → Secrets → Actions:
 *   - HUBSPOT_SANDBOX_TOKEN  (private app access token from a Developer test
 *                              account)
 *
 * Default phpunit.xml.dist excludes the "contract" group, so this only runs
 * via `composer test-contract` (phpunit-contract.xml.dist) or the weekly cron
 * GitHub Actions workflow.
 */
#[Group('contract')]
final class HubSpotContractTest extends TestCase
{
    private HubSpotSettings $settings;

    protected function setUp(): void
    {
        $token = $_SERVER['HUBSPOT_SANDBOX_TOKEN'] ?? $_ENV['HUBSPOT_SANDBOX_TOKEN'] ?? null;

        if (!$token) {
            self::markTestSkipped('HubSpot sandbox token not provided (set HUBSPOT_SANDBOX_TOKEN).');
        }

        $this->settings = new HubSpotSettings();
        $this->settings->accessToken = $token;
    }

    public function testConnectionAgainstRealSandbox(): void
    {
        $client = new HubSpotClient(HttpClient::create(), new NullLogger());

        static::assertTrue(
            $client->testConnection($this->settings),
            'HubSpot sandbox /crm/v3/objects/contacts did not return 200.',
        );
    }

    public function testGetAccountInfoReturnsKnownShape(): void
    {
        $client = new HubSpotClient(HttpClient::create(), new NullLogger());

        $accountInfo = $client->getAccountInfo($this->settings);

        static::assertNotNull($accountInfo, 'Account info call returned null.');
        static::assertIsArray($accountInfo);
        static::assertArrayHasKey(
            'portalId',
            $accountInfo,
            'HubSpot account info no longer exposes "portalId" — schema changed.',
        );
    }

    public function testGetDealPipelinesIsArray(): void
    {
        $client = new HubSpotClient(HttpClient::create(), new NullLogger());

        $pipelines = $client->getDealPipelines($this->settings);

        static::assertIsArray($pipelines, 'getDealPipelines() must return an array.');
    }
}

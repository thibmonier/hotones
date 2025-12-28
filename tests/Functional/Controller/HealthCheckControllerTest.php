<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use DateTime;
use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckControllerTest extends WebTestCase
{
    public function testHealthCheckReturnsHealthyStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($content);
        $this->assertArrayHasKey('status', $content);
        $this->assertArrayHasKey('timestamp', $content);
        $this->assertArrayHasKey('checks', $content);
        $this->assertArrayHasKey('metadata', $content);

        $this->assertSame('healthy', $content['status']);

        // Verify all checks are present
        $this->assertArrayHasKey('database', $content['checks']);
        $this->assertArrayHasKey('cache', $content['checks']);
        $this->assertArrayHasKey('filesystem', $content['checks']);

        // Verify each check has status and message
        foreach ($content['checks'] as $checkName => $check) {
            $this->assertArrayHasKey('status', $check, "Check '{$checkName}' missing status");
            $this->assertArrayHasKey('message', $check, "Check '{$checkName}' missing message");
            $this->assertSame('healthy', $check['status'], "Check '{$checkName}' is not healthy");
        }

        // Verify metadata
        $this->assertArrayHasKey('version', $content['metadata']);
        $this->assertArrayHasKey('symfony_version', $content['metadata']);
        $this->assertArrayHasKey('php_version', $content['metadata']);
        $this->assertArrayHasKey('environment', $content['metadata']);
    }

    public function testHealthCheckIsPubliclyAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        // Should be accessible without authentication
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testLivenessProbeReturnsAliveStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health/live');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($content);
        $this->assertArrayHasKey('status', $content);
        $this->assertArrayHasKey('timestamp', $content);
        $this->assertSame('alive', $content['status']);
    }

    public function testReadinessProbeReturnsReadyStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health/ready');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($content);
        $this->assertArrayHasKey('status', $content);
        $this->assertArrayHasKey('timestamp', $content);
        $this->assertArrayHasKey('checks', $content);

        $this->assertSame('ready', $content['status']);

        // Verify critical checks
        $this->assertArrayHasKey('database', $content['checks']);
        $this->assertArrayHasKey('cache', $content['checks']);

        $this->assertSame('ready', $content['checks']['database']);
        $this->assertSame('ready', $content['checks']['cache']);
    }

    public function testHealthCheckReturnsValidTimestamp(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('timestamp', $content);

        // Verify timestamp is in ISO 8601 format (ATOM)
        $timestamp = DateTime::createFromFormat(DateTimeInterface::ATOM, $content['timestamp']);
        $this->assertInstanceOf(DateTime::class, $timestamp);

        // Verify timestamp is recent (within last 5 seconds)
        $now  = new DateTime();
        $diff = $now->getTimestamp() - $timestamp->getTimestamp();
        $this->assertLessThan(5, $diff, 'Timestamp should be recent');
    }

    public function testHealthCheckDatabaseCheckWorksCorrectly(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('database', $content['checks']);
        $this->assertSame('healthy', $content['checks']['database']['status']);
        $this->assertStringContainsString('successful', $content['checks']['database']['message']);
    }

    public function testHealthCheckCacheCheckWorksCorrectly(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('cache', $content['checks']);
        $this->assertSame('healthy', $content['checks']['cache']['status']);
        $this->assertStringContainsString('operational', $content['checks']['cache']['message']);
    }

    public function testHealthCheckFilesystemCheckWorksCorrectly(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('filesystem', $content['checks']);
        $this->assertSame('healthy', $content['checks']['filesystem']['status']);
        $this->assertStringContainsString('writable', $content['checks']['filesystem']['message']);
    }

    public function testHealthCheckMetadataContainsVersionInformation(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('metadata', $content);
        $this->assertNotEmpty($content['metadata']['version']);
        $this->assertNotEmpty($content['metadata']['symfony_version']);
        $this->assertNotEmpty($content['metadata']['php_version']);
        $this->assertSame('test', $content['metadata']['environment']);
    }
}

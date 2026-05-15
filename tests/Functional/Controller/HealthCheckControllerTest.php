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

        static::assertIsArray($content);
        static::assertArrayHasKey('status', $content);
        static::assertArrayHasKey('timestamp', $content);
        static::assertArrayHasKey('checks', $content);
        static::assertArrayHasKey('metadata', $content);

        static::assertSame('healthy', $content['status']);

        // Verify all checks are present
        static::assertArrayHasKey('database', $content['checks']);
        static::assertArrayHasKey('cache', $content['checks']);
        static::assertArrayHasKey('filesystem', $content['checks']);

        // Verify each check has status and message
        foreach ($content['checks'] as $checkName => $check) {
            static::assertArrayHasKey('status', $check, "Check '{$checkName}' missing status");
            static::assertArrayHasKey('message', $check, "Check '{$checkName}' missing message");
            static::assertSame('healthy', $check['status'], "Check '{$checkName}' is not healthy");
        }

        // Verify metadata
        static::assertArrayHasKey('version', $content['metadata']);
        static::assertArrayHasKey('symfony_version', $content['metadata']);
        static::assertArrayHasKey('php_version', $content['metadata']);
        static::assertArrayHasKey('environment', $content['metadata']);
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

        static::assertIsArray($content);
        static::assertArrayHasKey('status', $content);
        static::assertArrayHasKey('timestamp', $content);
        static::assertSame('alive', $content['status']);
    }

    public function testReadinessProbeReturnsReadyStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health/ready');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = json_decode($client->getResponse()->getContent(), true);

        static::assertIsArray($content);
        static::assertArrayHasKey('status', $content);
        static::assertArrayHasKey('timestamp', $content);
        static::assertArrayHasKey('checks', $content);

        static::assertSame('ready', $content['status']);

        // Verify critical checks
        static::assertArrayHasKey('database', $content['checks']);
        static::assertArrayHasKey('cache', $content['checks']);

        static::assertSame('ready', $content['checks']['database']);
        static::assertSame('ready', $content['checks']['cache']);
    }

    public function testHealthCheckReturnsValidTimestamp(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $content = json_decode($client->getResponse()->getContent(), true);

        static::assertArrayHasKey('timestamp', $content);

        // Verify timestamp is in ISO 8601 format (ATOM)
        $timestamp = DateTime::createFromFormat(DateTimeInterface::ATOM, $content['timestamp']);
        static::assertInstanceOf(DateTime::class, $timestamp);

        // Verify timestamp is recent (within last 5 seconds)
        $now = new DateTime();
        $diff = $now->getTimestamp() - $timestamp->getTimestamp();
        static::assertLessThan(5, $diff, 'Timestamp should be recent');
    }

    public function testHealthCheckDatabaseCheckWorksCorrectly(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $content = json_decode($client->getResponse()->getContent(), true);

        static::assertArrayHasKey('database', $content['checks']);
        static::assertSame('healthy', $content['checks']['database']['status']);
        static::assertStringContainsString('successful', $content['checks']['database']['message']);
    }

    public function testHealthCheckCacheCheckWorksCorrectly(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $content = json_decode($client->getResponse()->getContent(), true);

        static::assertArrayHasKey('cache', $content['checks']);
        static::assertSame('healthy', $content['checks']['cache']['status']);
        static::assertStringContainsString('operational', $content['checks']['cache']['message']);
    }

    public function testHealthCheckFilesystemCheckWorksCorrectly(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $content = json_decode($client->getResponse()->getContent(), true);

        static::assertArrayHasKey('filesystem', $content['checks']);
        static::assertSame('healthy', $content['checks']['filesystem']['status']);
        static::assertStringContainsString('writable', $content['checks']['filesystem']['message']);
    }

    public function testHealthCheckMetadataContainsVersionInformation(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $content = json_decode($client->getResponse()->getContent(), true);

        static::assertArrayHasKey('metadata', $content);
        static::assertNotEmpty($content['metadata']['version']);
        static::assertNotEmpty($content['metadata']['symfony_version']);
        static::assertNotEmpty($content['metadata']['php_version']);
        static::assertSame('test', $content['metadata']['environment']);
    }
}

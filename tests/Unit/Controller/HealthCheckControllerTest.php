<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\HealthCheckController;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Unit tests for HealthCheckController failure paths (TEST-008, sprint-004).
 *
 * The functional test (App\Tests\Functional\Controller\HealthCheckControllerTest)
 * covers the happy path where every dependency is healthy. This test
 * complements it by exercising:
 *   - Doctrine connectivity failure -> 503 + per-check unhealthy message
 *   - Cache backend failure -> 503 + per-check unhealthy message
 *   - Composite failures (DB + cache down at once)
 *   - Liveness probe always returning 200 regardless of dependencies
 *   - Readiness probe degrading to 503 when a dependency is down
 *
 * Closes gap-analysis Critical #3 (Healthcheck Doctrine non couvert).
 */
#[AllowMockObjectsWithoutExpectations]
final class HealthCheckControllerTest extends TestCase
{
    private Connection&MockObject $connection;
    private CacheItemPoolInterface&MockObject $cache;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
    }

    public function testCheckReturnsServiceUnavailableWhenDatabaseFails(): void
    {
        $this->connection
            ->method('executeQuery')
            ->willThrowException($this->makeDoctrineException('connection refused'));

        $this->stubHealthyCache();

        $controller = $this->buildController();

        $response = $controller->check();

        static::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());

        $payload = $this->decode($response);
        static::assertSame('unhealthy', $payload['status']);
        static::assertSame('unhealthy', $payload['checks']['database']['status']);
        static::assertStringContainsString('connection refused', $payload['checks']['database']['message']);
        // Cache + filesystem should still be reported, allowing operators to triage.
        static::assertArrayHasKey('cache', $payload['checks']);
        static::assertArrayHasKey('filesystem', $payload['checks']);
    }

    public function testCheckReturnsServiceUnavailableWhenCacheFails(): void
    {
        $this->stubHealthyDatabase();

        $this->cache->method('getItem')->willThrowException(new RuntimeException('redis unreachable'));

        $controller = $this->buildController();

        $response = $controller->check();

        static::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());

        $payload = $this->decode($response);
        static::assertSame('unhealthy', $payload['status']);
        static::assertSame('unhealthy', $payload['checks']['cache']['status']);
        static::assertSame('healthy', $payload['checks']['database']['status']);
        static::assertStringContainsString('redis unreachable', $payload['checks']['cache']['message']);
    }

    public function testCheckAggregatesMultipleFailures(): void
    {
        $this->connection->method('executeQuery')->willThrowException($this->makeDoctrineException('db down'));

        $this->cache->method('getItem')->willThrowException(new RuntimeException('cache down'));

        $controller = $this->buildController();

        $response = $controller->check();

        static::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());

        $payload = $this->decode($response);
        static::assertSame('unhealthy', $payload['status']);
        static::assertSame('unhealthy', $payload['checks']['database']['status']);
        static::assertSame('unhealthy', $payload['checks']['cache']['status']);
    }

    public function testReadinessReportsNotReadyWhenDatabaseDown(): void
    {
        $this->connection->method('executeQuery')->willThrowException($this->makeDoctrineException('db down'));

        $this->stubHealthyCache();

        $controller = $this->buildController();

        $response = $controller->readiness();

        static::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());

        $payload = $this->decode($response);
        static::assertSame('not ready', $payload['status']);
        static::assertSame('not ready', $payload['checks']['database']);
        static::assertSame('ready', $payload['checks']['cache']);
    }

    public function testLivenessIsAlwaysAlive(): void
    {
        // Even with broken dependencies, liveness must return 200 — its purpose
        // is to tell the orchestrator the PHP process itself is alive.
        $this->connection->expects(self::never())->method(static::anything());
        $this->cache->expects(self::never())->method(static::anything());

        $controller = $this->buildController();

        $response = $controller->liveness();

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame('alive', $this->decode($response)['status']);
    }

    public function testCheckReportsHealthyWhenEverythingWorks(): void
    {
        $this->stubHealthyDatabase();
        $this->stubHealthyCache();

        $controller = $this->buildController();

        $response = $controller->check();

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $payload = $this->decode($response);
        static::assertSame('healthy', $payload['status']);
        static::assertSame('healthy', $payload['checks']['database']['status']);
        static::assertSame('healthy', $payload['checks']['cache']['status']);
        static::assertSame('healthy', $payload['checks']['filesystem']['status']);
    }

    private function stubHealthyDatabase(): void
    {
        $result = $this->createStub(Result::class);
        $result->method('fetchOne')->willReturn(1);

        $this->connection->method('executeQuery')->willReturn($result);
    }

    private function stubHealthyCache(): void
    {
        $store = [];

        $itemFactory = function (string $key) use (&$store): CacheItemInterface {
            $item = $this->createStub(CacheItemInterface::class);
            $item->method('isHit')->willReturnCallback(static fn (): bool => isset($store[$key]));
            $item->method('get')->willReturnCallback(static fn () => $store[$key] ?? null);
            $item->method('set')->willReturnCallback(static function ($value) use ($item, $key, &$store) {
                $store[$key] = $value;

                return $item;
            });
            $item->method('expiresAfter')->willReturnSelf();

            return $item;
        };

        $this->cache->method('getItem')->willReturnCallback($itemFactory);
        $this->cache->method('save')->willReturn(true);
        $this->cache
            ->method('deleteItem')
            ->willReturnCallback(static function (string $key) use (&$store) {
                unset($store[$key]);

                return true;
            });
    }

    private function buildController(): HealthCheckController
    {
        $container = new Container();
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir());
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.environment', 'test');
        $container->set('parameter_bag', new ContainerBag($container));
        $container->set('serializer', new Serializer([new ObjectNormalizer()], [new JsonEncoder()]));

        $controller = new HealthCheckController($this->connection, $this->cache);
        $controller->setContainer($container);

        return $controller;
    }

    /**
     * Build a database error analogue. The controller catches \Exception (any
     * descendant works), so a RuntimeException is functionally equivalent to a
     * Doctrine driver exception for the purpose of this test, while avoiding
     * the headache of constructing a concrete Doctrine\DBAL\Exception in DBAL 4
     * where the root class became an interface.
     */
    private function makeDoctrineException(string $message): RuntimeException
    {
        return new RuntimeException($message);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        $content = (string) $response->getContent();
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        /* @var array<string, mixed> $decoded */
        return $decoded;
    }
}

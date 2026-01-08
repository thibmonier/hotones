<?php

declare(strict_types=1);

namespace App\Controller;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Exception;

use const PHP_VERSION;

use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Attribute\Route;

class HealthCheckController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * Health check endpoint for monitoring and orchestration tools.
     *
     * Returns HTTP 200 if all checks pass, HTTP 503 if any check fails.
     */
    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function check(): JsonResponse
    {
        $checks        = [];
        $overallStatus = 'healthy';
        $httpStatus    = Response::HTTP_OK;

        // Check 1: Database connectivity
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();
            $checks['database'] = [
                'status'  => 'healthy',
                'message' => 'Database connection successful',
            ];
        } catch (Exception $e) {
            $checks['database'] = [
                'status'  => 'unhealthy',
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
            $overallStatus = 'unhealthy';
            $httpStatus    = Response::HTTP_SERVICE_UNAVAILABLE;
        }

        // Check 2: Redis/Cache connectivity
        try {
            $testItem = $this->cache->getItem('health_check_test');
            $testItem->set('test_value');
            $testItem->expiresAfter(10);
            $this->cache->save($testItem);

            $retrievedItem = $this->cache->getItem('health_check_test');
            if ($retrievedItem->isHit() && $retrievedItem->get() === 'test_value') {
                $checks['cache'] = [
                    'status'  => 'healthy',
                    'message' => 'Cache system operational',
                ];
            } else {
                $checks['cache'] = [
                    'status'  => 'unhealthy',
                    'message' => 'Cache read/write failed',
                ];
                $overallStatus = 'unhealthy';
                $httpStatus    = Response::HTTP_SERVICE_UNAVAILABLE;
            }

            // Clean up test item
            $this->cache->deleteItem('health_check_test');
        } catch (Exception $e) {
            $checks['cache'] = [
                'status'  => 'unhealthy',
                'message' => 'Cache system failed: '.$e->getMessage(),
            ];
            $overallStatus = 'unhealthy';
            $httpStatus    = Response::HTTP_SERVICE_UNAVAILABLE;
        }

        // Check 3: Filesystem writability (var/cache)
        $cacheDir = $this->getParameter('kernel.cache_dir');
        $testFile = $cacheDir.'/health_check_test.tmp';

        try {
            if (file_put_contents($testFile, 'test') === false) {
                throw new RuntimeException('Failed to write to cache directory');
            }

            if (file_get_contents($testFile) !== 'test') {
                throw new RuntimeException('Failed to read from cache directory');
            }

            unlink($testFile);

            $checks['filesystem'] = [
                'status'  => 'healthy',
                'message' => 'Filesystem is writable',
            ];
        } catch (Exception $e) {
            $checks['filesystem'] = [
                'status'  => 'unhealthy',
                'message' => 'Filesystem check failed: '.$e->getMessage(),
            ];
            $overallStatus = 'unhealthy';
            $httpStatus    = Response::HTTP_SERVICE_UNAVAILABLE;
        }

        // Application metadata
        $metadata = [
            'version'         => $this->getParameter('kernel.project_dir').'/VERSION',
            'symfony_version' => Kernel::VERSION,
            'php_version'     => PHP_VERSION,
            'environment'     => $this->getParameter('kernel.environment'),
        ];

        // Try to read version file if it exists
        $versionFile = $this->getParameter('kernel.project_dir').'/VERSION';
        if (file_exists($versionFile)) {
            $metadata['version'] = trim(file_get_contents($versionFile));
        } else {
            $metadata['version'] = 'unknown';
        }

        return $this->json([
            'status'    => $overallStatus,
            'timestamp' => new DateTime()->format(DateTimeInterface::ATOM),
            'checks'    => $checks,
            'metadata'  => $metadata,
        ], $httpStatus);
    }

    /**
     * Lightweight liveness probe for Kubernetes/Docker.
     *
     * Returns HTTP 200 if the application is running.
     * Does not perform deep health checks.
     */
    #[Route('/health/live', name: 'health_liveness', methods: ['GET'])]
    public function liveness(): JsonResponse
    {
        return $this->json([
            'status'    => 'alive',
            'timestamp' => new DateTime()->format(DateTimeInterface::ATOM),
        ]);
    }

    /**
     * Readiness probe for Kubernetes/Docker.
     *
     * Returns HTTP 200 if the application is ready to serve traffic.
     * Checks critical dependencies (database, cache).
     */
    #[Route('/health/ready', name: 'health_readiness', methods: ['GET'])]
    public function readiness(): JsonResponse
    {
        $ready      = true;
        $httpStatus = Response::HTTP_OK;
        $checks     = [];

        // Check database
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();
            $checks['database'] = 'ready';
        } catch (Exception) {
            $checks['database'] = 'not ready';
            $ready              = false;
            $httpStatus         = Response::HTTP_SERVICE_UNAVAILABLE;
        }

        // Check cache
        try {
            $testItem = $this->cache->getItem('readiness_check');
            $testItem->set('ready');
            $this->cache->save($testItem);
            $this->cache->deleteItem('readiness_check');
            $checks['cache'] = 'ready';
        } catch (Exception) {
            $checks['cache'] = 'not ready';
            $ready           = false;
            $httpStatus      = Response::HTTP_SERVICE_UNAVAILABLE;
        }

        return $this->json([
            'status'    => $ready ? 'ready' : 'not ready',
            'timestamp' => new DateTime()->format(DateTimeInterface::ATOM),
            'checks'    => $checks,
        ], $httpStatus);
    }
}

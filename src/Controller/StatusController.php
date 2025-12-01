<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class StatusController extends AbstractController
{
    #[Route('/status', name: 'app_status', methods: ['GET'])]
    public function __invoke(Connection $connection): Response
    {
        try {
            // Simple DB ping
            $connection->executeQuery('SELECT 1')->fetchOne();

            return new JsonResponse([
                'status' => 'ok',
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => 'Database unreachable',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}

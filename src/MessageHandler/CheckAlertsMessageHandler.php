<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CheckAlertsMessage;
use App\Service\AlertDetectionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CheckAlertsMessageHandler
{
    public function __construct(
        private readonly AlertDetectionService $alertDetectionService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CheckAlertsMessage $message): void
    {
        $this->logger->info('Starting alert check...');

        $stats = $this->alertDetectionService->checkAllAlerts();

        $totalAlerts = array_sum($stats);

        $this->logger->info('Alert check completed', [
            'budget_alerts'   => $stats['budget_alerts'],
            'margin_alerts'   => $stats['margin_alerts'],
            'overload_alerts' => $stats['overload_alerts'],
            'payment_alerts'  => $stats['payment_alerts'],
            'total_alerts'    => $totalAlerts,
        ]);
    }
}

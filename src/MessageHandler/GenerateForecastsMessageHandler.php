<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\GenerateForecastsMessage;
use App\Service\ForecastingService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GenerateForecastsMessageHandler
{
    public function __construct(
        private ForecastingService $forecastingService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(GenerateForecastsMessage $message): void
    {
        $months = $message->getMonths();

        $this->logger->info('[Scheduler] Début de la génération des prévisions', [
            'months' => $months,
        ]);

        try {
            $forecasts = $this->forecastingService->generateForecasts($months);

            $this->logger->info('[Scheduler] Prévisions générées avec succès', [
                'forecasts_count' => count($forecasts),
                'months'          => $months,
            ]);
        } catch (Exception $e) {
            $this->logger->error('[Scheduler] Erreur lors de la génération des prévisions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

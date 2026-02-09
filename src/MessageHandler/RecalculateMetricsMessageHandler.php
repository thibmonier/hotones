<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\RecalculateMetricsMessage;
use App\Service\Analytics\MetricsCalculationService;
use DateTime;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RecalculateMetricsMessageHandler
{
    public function __construct(
        private MetricsCalculationService $service,
    ) {
    }

    public function __invoke(RecalculateMetricsMessage $message): void
    {
        $date = new DateTime($message->date);
        $this->service->calculateMetricsForPeriod($date, $message->granularity);
    }
}

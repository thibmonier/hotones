<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Application\Project\Query\RevenueForecastKpi\ComputeRevenueForecastKpiHandler;
use App\Domain\Order\Event\OrderStatusChangedEvent;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Alerte Slack quand forecast 30 j passe sous le seuil rouge plancher
 * trésorerie (US-114 T-114-05).
 *
 * Trigger : changement statut Order → recompute forecast → vérifier seuil.
 * Pattern hiérarchique configurabilité seuils (US-108) à compléter sprint-026
 * si override par société requis.
 */
#[AsMessageHandler]
final readonly class SendRevenueForecastRedAlertOnOrderStatusChanged
{
    public const float DEFAULT_RED_THRESHOLD_EUROS = 5_000.0;

    public function __construct(
        private ComputeRevenueForecastKpiHandler $computeRevenueForecastKpi,
        private SlackAlertingInterface $slackAlertingService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(OrderStatusChangedEvent $event): void
    {
        $forecast = ($this->computeRevenueForecastKpi)();

        // Seuil rouge plancher : alerte si forecast > 0 ET < seuil
        // (forecast = 0 sur pipeline vide n'est pas un signal exploitable).
        if ($forecast->forecast30Euros >= self::DEFAULT_RED_THRESHOLD_EUROS
            || $forecast->forecast30Euros <= 0.0) {
            return;
        }

        $title = sprintf(
            '🚨 Revenue forecast 30j sous seuil plancher trésorerie (%.0f € < %.0f €)',
            $forecast->forecast30Euros,
            self::DEFAULT_RED_THRESHOLD_EUROS,
        );

        $body = sprintf(
            "Forecast 30 jours : *%.0f €* (seuil rouge %.0f €)\n".
            "Forecast 90 jours : %.0f €\n".
            "Commandes confirmées : %.0f €\n".
            "Devis pondérés (coef %.0f %%) : %.0f €\n\n".
            'Investiguer le pipeline commercial : relancer les devis a_signer + nouveaux contacts.',
            $forecast->forecast30Euros,
            self::DEFAULT_RED_THRESHOLD_EUROS,
            $forecast->forecast90Euros,
            $forecast->confirmedEuros,
            $forecast->probabilityCoefficient * 100,
            $forecast->weightedQuotesEuros,
        );

        $sent = $this->slackAlertingService->sendAlert($title, $body, AlertSeverity::CRITICAL);

        $this->logger->info('Revenue forecast red alert triggered', [
            'order_id' => (string) $event->getOrderId(),
            'previous_status' => $event->getPreviousStatus()->value,
            'new_status' => $event->getNewStatus()->value,
            'forecast_30_euros' => $forecast->forecast30Euros,
            'threshold_euros' => self::DEFAULT_RED_THRESHOLD_EUROS,
            'slack_sent' => $sent,
        ]);
    }
}

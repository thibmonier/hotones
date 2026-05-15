<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Application\Project\Query\ConversionRateKpi\ComputeConversionRateKpiHandler;
use App\Domain\Order\Event\OrderStatusChangedEvent;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Alerte Slack quand taux de conversion 30j passe sous le seuil rouge (US-115 T-115-05).
 *
 * Trigger : OrderStatusChangedEvent → recompute taux → vérifier seuil.
 * Pattern hiérarchique seuils US-108 (TODO sprint-026 si override société requis).
 */
#[AsMessageHandler]
final readonly class SendConversionRateRedAlertOnOrderStatusChanged
{
    public const float DEFAULT_RED_THRESHOLD_PERCENT = 25.0;

    public function __construct(
        private ComputeConversionRateKpiHandler $computeConversionRateKpi,
        private SlackAlertingInterface $slackAlertingService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(OrderStatusChangedEvent $event): void
    {
        // Ancre temporelle = timestamp événement → testabilité deterministe.
        $rate = ($this->computeConversionRateKpi)($event->getOccurredOn());

        // Pas d'alerte si pas de devis émis (spam guard pipeline vide).
        if ($rate->emitted30Count === 0) {
            return;
        }

        if ($rate->rate30Percent >= self::DEFAULT_RED_THRESHOLD_PERCENT) {
            return;
        }

        $title = sprintf(
            '🚨 Taux de conversion 30j sous seuil rouge (%.1f %% < %.0f %%)',
            $rate->rate30Percent,
            self::DEFAULT_RED_THRESHOLD_PERCENT,
        );

        $body = sprintf(
            "Taux conversion 30 jours : *%.1f %%* (seuil rouge %.0f %%)\n".
            "Détail : %d signés / %d devis émis\n".
            "Taux 90 jours : %.1f %%\n".
            "Taux 365 jours : %.1f %%\n".
            "Tendance 30j vs période précédente : %s %s\n\n".
            'Investiguer la performance commerciale : qualification leads, suivi devis perdus.',
            $rate->rate30Percent,
            self::DEFAULT_RED_THRESHOLD_PERCENT,
            $rate->converted30Count,
            $rate->emitted30Count,
            $rate->rate90Percent,
            $rate->rate365Percent,
            $rate->trend30->symbol(),
            $rate->trend30->label(),
        );

        $sent = $this->slackAlertingService->sendAlert($title, $body, AlertSeverity::CRITICAL);

        $this->logger->info('Conversion rate red alert triggered', [
            'order_id' => (string) $event->getOrderId(),
            'previous_status' => $event->getPreviousStatus()->value,
            'new_status' => $event->getNewStatus()->value,
            'rate_30_percent' => $rate->rate30Percent,
            'threshold_percent' => self::DEFAULT_RED_THRESHOLD_PERCENT,
            'slack_sent' => $sent,
        ]);
    }
}

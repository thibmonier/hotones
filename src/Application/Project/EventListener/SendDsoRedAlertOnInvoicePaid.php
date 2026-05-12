<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Application\Project\Query\DsoKpi\ComputeDsoKpiHandler;
use App\Domain\Invoice\Event\InvoicePaidEvent;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Sends a Slack alert when the 30-day rolling DSO crosses the red threshold
 * after an invoice is paid.
 *
 * Triggered by {@see InvoicePaidEvent}. Recomputes DSO via
 * {@see ComputeDsoKpiHandler} (cache was invalidated by
 * {@see InvalidateDsoCacheOnInvoicePaid} in the same event handling cycle).
 *
 * Default red threshold = 60 days (US-110 AC). Warning (45 days) is rendered
 * visually on the dashboard widget (T-110-04) without alerting.
 *
 * Hierarchical threshold (global → Client) postponed — pattern US-108
 * applies once a {@code DsoThreshold} configuration entity is introduced.
 *
 * EPIC-003 Phase 4 sprint-024 US-110 T-110-05.
 */
#[AsMessageHandler]
final readonly class SendDsoRedAlertOnInvoicePaid
{
    public const float DEFAULT_RED_THRESHOLD_DAYS = 60.0;

    public function __construct(
        private ComputeDsoKpiHandler $computeDsoKpi,
        private SlackAlertingInterface $slackAlertingService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(InvoicePaidEvent $event): void
    {
        $dso = ($this->computeDsoKpi)();

        if ($dso->dso30Days <= self::DEFAULT_RED_THRESHOLD_DAYS) {
            return;
        }

        $title = sprintf(
            '🚨 DSO 30j au-dessus du seuil rouge (%.1f j > %.0f j)',
            $dso->dso30Days,
            self::DEFAULT_RED_THRESHOLD_DAYS,
        );

        $body = sprintf(
            "DSO 30 jours : *%.1f j* (seuil rouge %.0f j)\n".
            "DSO 90 jours : %.1f j\n".
            "DSO 365 jours : %.1f j\n".
            "Tendance 30j vs période précédente : %s %s\n\n".
            'Investiguer les factures impayées et relancer les clients lents.',
            $dso->dso30Days,
            self::DEFAULT_RED_THRESHOLD_DAYS,
            $dso->dso90Days,
            $dso->dso365Days,
            $dso->trend30->symbol(),
            $dso->trend30->label(),
        );

        $sent = $this->slackAlertingService->sendAlert($title, $body, AlertSeverity::CRITICAL);

        $this->logger->info('DSO red alert triggered', [
            'invoice_id' => (string) $event->getInvoiceId(),
            'dso_30_days' => $dso->dso30Days,
            'threshold_days' => self::DEFAULT_RED_THRESHOLD_DAYS,
            'slack_sent' => $sent,
        ]);
    }
}

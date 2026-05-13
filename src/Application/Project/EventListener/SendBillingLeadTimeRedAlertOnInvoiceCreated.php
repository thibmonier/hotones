<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Application\Project\Query\BillingLeadTimeKpi\ComputeBillingLeadTimeKpiHandler;
use App\Domain\Invoice\Event\InvoiceCreatedEvent;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Sends a Slack alert when the 30-day rolling billing lead time median
 * crosses the red threshold after an invoice is created (emitted).
 *
 * Triggered by {@see InvoiceCreatedEvent}. Recomputes lead time KPI via
 * {@see ComputeBillingLeadTimeKpiHandler} (cache was invalidated by
 * {@see InvalidateBillingLeadTimeCacheOnInvoiceCreated} in the same event
 * handling cycle).
 *
 * Default red threshold = 30 days (US-111 AC). Warning (14 days) is rendered
 * visually on the dashboard widget (T-111-04) without alerting.
 *
 * Hierarchical threshold (global → Client) postponed — pattern US-108
 * applies once a {@code BillingLeadTimeThreshold} configuration entity
 * is introduced.
 *
 * EPIC-003 Phase 4 sprint-024 US-111 T-111-05.
 */
#[AsMessageHandler]
final readonly class SendBillingLeadTimeRedAlertOnInvoiceCreated
{
    public const float DEFAULT_RED_THRESHOLD_DAYS = 30.0;

    public function __construct(
        private ComputeBillingLeadTimeKpiHandler $computeBillingLeadTimeKpi,
        private SlackAlertingInterface $slackAlertingService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(InvoiceCreatedEvent $event): void
    {
        $kpi = ($this->computeBillingLeadTimeKpi)();

        $median30 = $kpi->stats30->p50->getDays();

        if ($median30 <= self::DEFAULT_RED_THRESHOLD_DAYS) {
            return;
        }

        $title = sprintf(
            '🚨 Temps de facturation médian 30j au-dessus du seuil rouge (%.1f j > %.0f j)',
            $median30,
            self::DEFAULT_RED_THRESHOLD_DAYS,
        );

        $body = sprintf(
            "Médiane 30 jours : *%.1f j* (seuil rouge %.0f j)\n".
            "p75 30j : %.1f j  ·  p95 30j : %.1f j\n".
            "Médiane 90 jours : %.1f j  ·  365 jours : %.1f j\n".
            "%s\n\n".
            'Investiguer les goulots d’étranglement compta vs commercial.',
            $median30,
            self::DEFAULT_RED_THRESHOLD_DAYS,
            $kpi->stats30->p75->getDays(),
            $kpi->stats30->p95->getDays(),
            $kpi->stats90->p50->getDays(),
            $kpi->stats365->p50->getDays(),
            $this->formatTopSlowClients($kpi->topSlowClients),
        );

        $sent = $this->slackAlertingService->sendAlert($title, $body, AlertSeverity::CRITICAL);

        $this->logger->info('Billing lead time red alert triggered', [
            'invoice_id' => (string) $event->getInvoiceId(),
            'median_30_days' => $median30,
            'threshold_days' => self::DEFAULT_RED_THRESHOLD_DAYS,
            'slack_sent' => $sent,
        ]);
    }

    /**
     * @param list<\App\Application\Project\Query\BillingLeadTimeKpi\TopSlowClientDto> $topClients
     */
    private function formatTopSlowClients(array $topClients): string
    {
        if ($topClients === []) {
            return '';
        }

        $lines = ["\nTop 3 clients lents (30j) :"];
        foreach ($topClients as $index => $client) {
            $lines[] = sprintf(
                '  %d. %s — %.1f j (%d facture%s)',
                $index + 1,
                $client->clientName,
                $client->averageLeadTimeDays,
                $client->sampleCount,
                $client->sampleCount > 1 ? 's' : '',
            );
        }

        return implode("\n", $lines);
    }
}

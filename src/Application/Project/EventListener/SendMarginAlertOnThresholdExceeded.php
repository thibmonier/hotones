<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Domain\Project\Event\MarginThresholdExceededEvent;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * EPIC-003 Phase 3 (sprint-021 US-103) — handler async cross-aggregate
 * Application Layer ACL Project → Notification.
 *
 * Consume `MarginThresholdExceededEvent` + dispatche alerte Slack
 * `#alerts-prod` via `SlackAlertingService` (réutilisé US-094 sprint-017).
 *
 * Severity :
 * - CRITICAL si marge < threshold/2 (ex: < 5 % avec threshold 10 %)
 * - WARN sinon (marge < threshold mais > threshold/2)
 *
 * Dégradé silent si webhook URL non configuré (`SlackAlertingService` log
 * debug + retourne false). Pattern OPS atelier sprint-021 J-2 décision AT-1
 * option B (livraison partielle staging-only acceptée si webhook prod
 * pas configuré J0).
 *
 * Dedup logique 24h reportée sprint-022+ si bruit détecté (cache Redis avec
 * TTL ou table `margin_alert_log`).
 */
#[AsMessageHandler]
final readonly class SendMarginAlertOnThresholdExceeded
{
    public function __construct(
        private SlackAlertingInterface $slackAlertingService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(MarginThresholdExceededEvent $event): void
    {
        $severity = $event->isCritical() ? AlertSeverity::CRITICAL : AlertSeverity::WARNING;

        $title = sprintf(
            '⚠️ Marge projet sous seuil — %s (%.1f %%)',
            $event->projectName,
            $event->marginPercent,
        );

        $body = sprintf(
            "Projet : *%s*\n".
            "Marge actuelle : *%.1f %%* (seuil : %.1f %%)\n".
            "Coût total : %.2f €\n".
            "Facturé payé : %.2f €\n".
            'ProjectId : `%s`',
            $event->projectName,
            $event->marginPercent,
            $event->thresholdPercent,
            $event->costTotal->getAmount(),
            $event->invoicedPaidTotal->getAmount(),
            $event->projectId,
        );

        $sent = $this->slackAlertingService->sendAlert($title, $body, $severity);

        $this->logger->info('MarginThresholdExceeded handled', [
            'project_id' => (string) $event->projectId,
            'project_name' => $event->projectName,
            'margin_percent' => $event->marginPercent,
            'threshold_percent' => $event->thresholdPercent,
            'severity' => $severity->value,
            'slack_sent' => $sent,
        ]);
    }
}

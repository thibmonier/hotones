<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Application\Project\Query\PortfolioMarginKpi\ComputePortfolioMarginKpiHandler;
use App\Domain\Project\Event\ProjectMarginRecalculatedEvent;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Alerte Slack quand la marge moyenne pondérée portefeuille passe sous le
 * seuil rouge (US-117 T-117-05).
 *
 * Trigger : {@see ProjectMarginRecalculatedEvent} → recompute KPI → vérifier
 * seuil rouge (10 % default, configurable hiérarchique pattern US-108
 * — TODO sprint-027 si override société requis).
 *
 * Ancrage temporel = `event.occurredOn` → testabilité déterministe.
 */
#[AsMessageHandler]
final readonly class SendPortfolioMarginRedAlertOnRecalculated
{
    public const float DEFAULT_RED_THRESHOLD_PERCENT = 10.0;

    public function __construct(
        private ComputePortfolioMarginKpiHandler $computePortfolioMarginKpi,
        private SlackAlertingInterface $slackAlertingService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProjectMarginRecalculatedEvent $event): void
    {
        $kpi = ($this->computePortfolioMarginKpi)($event->getOccurredOn());

        // Pas d'alerte sans portefeuille (spam guard).
        if ($kpi->projectsWithSnapshot === 0) {
            return;
        }

        if ($kpi->averagePercent >= self::DEFAULT_RED_THRESHOLD_PERCENT) {
            return;
        }

        $title = sprintf(
            '🚨 Marge moyenne portefeuille sous seuil rouge (%.1f %% < %.0f %%)',
            $kpi->averagePercent,
            self::DEFAULT_RED_THRESHOLD_PERCENT,
        );

        $body = sprintf(
            "Marge moyenne pondérée : *%.1f %%* (seuil rouge %.0f %%, cible %.0f %%)\n".
            "Projets pris en compte : %d (avec snapshot)\n".
            "Projets ≥ cible (%.0f %%) : %d\n".
            "Projets < cible (%.0f %%) : %d\n".
            "Projets sans snapshot : %d\n\n".
            'Investiguer : revoir tarification, contrôler coûts internes, identifier projets déficitaires.',
            $kpi->averagePercent,
            self::DEFAULT_RED_THRESHOLD_PERCENT,
            $kpi->targetMarginPercent,
            $kpi->projectsWithSnapshot,
            $kpi->targetMarginPercent,
            $kpi->projectsAboveTarget,
            $kpi->targetMarginPercent,
            $kpi->projectsBelowTarget,
            $kpi->projectsWithoutSnapshot,
        );

        $sent = $this->slackAlertingService->sendAlert($title, $body, AlertSeverity::CRITICAL);

        $this->logger->info('Portfolio margin red alert triggered', [
            'project_id' => $event->getAggregateId(),
            'project_name' => $event->projectName,
            'average_percent' => $kpi->averagePercent,
            'threshold_percent' => self::DEFAULT_RED_THRESHOLD_PERCENT,
            'projects_with_snapshot' => $kpi->projectsWithSnapshot,
            'slack_sent' => $sent,
        ]);
    }
}

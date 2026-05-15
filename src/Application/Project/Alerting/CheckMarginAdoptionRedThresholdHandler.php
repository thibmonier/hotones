<?php

declare(strict_types=1);

namespace App\Application\Project\Alerting;

use App\Application\Project\Query\MarginAdoptionKpi\ComputeMarginAdoptionKpiHandler;
use App\Security\CompanyContext;
use App\Service\Alerting\AlertSeverity;
use App\Service\Alerting\SlackAlertingInterface;
use DateTimeImmutable;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Check margin adoption red threshold persistance (7 consecutive days) and
 * fire Slack alert if condition met.
 *
 * Pattern aligné US-110 T-110-05 / US-111 T-111-05 mais avec persistance
 * state vs réactif sur event :
 *  - Red threshold default = 40 % (US-112 AC)
 *  - Streak threshold default = 7 consecutive days
 *
 * State stored in `cache.kpi` pool with key per company. Idempotent : safe
 * to call multiple times per day, only counts once per day.
 *
 * Designed for daily cron execution via {@see App\Command\CheckMarginAdoptionThresholdCommand}.
 *
 * EPIC-003 Phase 4 sprint-024 US-112 T-112-04.
 */
final readonly class CheckMarginAdoptionRedThresholdHandler
{
    public const float DEFAULT_RED_THRESHOLD_PERCENT = 40.0;
    public const int CONSECUTIVE_DAYS_TRIGGER = 7;
    private const string CACHE_KEY_PREFIX = 'margin_adoption.alert_state.company_';

    public function __construct(
        private ComputeMarginAdoptionKpiHandler $computeMarginAdoptionKpi,
        private CompanyContext $companyContext,
        private CacheItemPoolInterface $kpiCache,
        private SlackAlertingInterface $slackAlertingService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(?DateTimeImmutable $now = null): void
    {
        $now ??= new DateTimeImmutable();

        $kpi = ($this->computeMarginAdoptionKpi)($now);

        if ($kpi->stats->totalActive === 0) {
            $this->logger->info('Margin adoption alert skipped — no active projects', [
                'company_id' => $this->companyId(),
            ]);

            return;
        }

        $cacheKey = self::CACHE_KEY_PREFIX.$this->companyId();
        $state = $this->loadState($cacheKey);

        $isRed = $kpi->stats->freshPercent < self::DEFAULT_RED_THRESHOLD_PERCENT;

        $newState = $isRed
            ? $state->withRedToday($now)
            : $state->withGreenToday();

        if ($isRed && $newState->shouldFireAlert(self::CONSECUTIVE_DAYS_TRIGGER, $now)) {
            $this->fireAlert($kpi->stats->freshPercent, $newState->consecutiveRedDays);
            $newState = $newState->withAlertSentAt($now);
        }

        $this->saveState($cacheKey, $newState);

        $this->logger->info('Margin adoption threshold checked', [
            'company_id' => $this->companyId(),
            'fresh_percent' => $kpi->stats->freshPercent,
            'is_red' => $isRed,
            'consecutive_red_days' => $newState->consecutiveRedDays,
            'alert_fired' => $newState->lastAlertSentAt?->getTimestamp() === $now->getTimestamp(),
        ]);
    }

    private function loadState(string $key): MarginAdoptionAlertState
    {
        $item = $this->kpiCache->getItem($key);

        if (!$item->isHit()) {
            return MarginAdoptionAlertState::initial();
        }

        $value = $item->get();

        return $value instanceof MarginAdoptionAlertState
            ? $value
            : MarginAdoptionAlertState::initial();
    }

    private function saveState(string $key, MarginAdoptionAlertState $state): void
    {
        $item = $this->kpiCache->getItem($key);
        $item->set($state);
        // State persists 30 days max — auto-cleanup if cron stops running.
        $item->expiresAfter(30 * 86_400);
        $this->kpiCache->save($item);
    }

    private function fireAlert(float $freshPercent, int $consecutiveDays): void
    {
        $title = sprintf(
            '🚨 Adoption marge sous seuil rouge depuis %d jours (%.1f %% < %.0f %%)',
            $consecutiveDays,
            $freshPercent,
            self::DEFAULT_RED_THRESHOLD_PERCENT,
        );

        $body = sprintf(
            "Adoption marge temps réel : *%.1f %%* (seuil rouge %.0f %%)\n".
            "Jours consécutifs sous le seuil : *%d*\n\n".
            'Indicateur trigger abandon ADR-0013 cas 2 — re-évaluer la pertinence du calcul marge temps réel.',
            $freshPercent,
            self::DEFAULT_RED_THRESHOLD_PERCENT,
            $consecutiveDays,
        );

        $this->slackAlertingService->sendAlert($title, $body, AlertSeverity::CRITICAL);
    }

    private function companyId(): int
    {
        return $this->companyContext->getCurrentCompany()->getId() ?? 0;
    }
}

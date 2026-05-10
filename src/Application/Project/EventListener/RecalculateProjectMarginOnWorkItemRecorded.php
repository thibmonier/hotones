<?php

declare(strict_types=1);

namespace App\Application\Project\EventListener;

use App\Application\Project\UseCase\CalculateProjectMargin\CalculateProjectMarginCommand;
use App\Application\Project\UseCase\CalculateProjectMargin\CalculateProjectMarginUseCase;
use App\Domain\WorkItem\Event\WorkItemRecordedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * EPIC-003 Phase 3 (sprint-022 US-104) — handler async cross-aggregate
 * Application Layer ACL WorkItem → Project.
 *
 * Consume `WorkItemRecordedEvent` (US-099 sprint-021 livré) → déclenche
 * recalcul marge projet via `CalculateProjectMarginUseCase`.
 *
 * Latence acceptée < 10s (async via Symfony Messenger transport).
 *
 * Threshold default 10 % (ADR-0016 Q5.2). Override possible via env var
 * future si configurabilité hiérarchique sprint-023+ (Q5.1 D).
 */
#[AsMessageHandler]
final readonly class RecalculateProjectMarginOnWorkItemRecorded
{
    private const float DEFAULT_MARGIN_THRESHOLD_PERCENT = 10.0;

    public function __construct(
        private CalculateProjectMarginUseCase $calculateProjectMarginUseCase,
    ) {
    }

    public function __invoke(WorkItemRecordedEvent $event): void
    {
        $command = new CalculateProjectMarginCommand(
            projectIdLegacy: (string) $event->projectId,
            thresholdPercent: self::DEFAULT_MARGIN_THRESHOLD_PERCENT,
        );

        $this->calculateProjectMarginUseCase->execute($command);
    }
}

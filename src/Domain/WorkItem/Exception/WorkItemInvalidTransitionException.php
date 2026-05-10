<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Exception;

use App\Domain\WorkItem\ValueObject\WorkItemId;
use App\Domain\WorkItem\ValueObject\WorkItemStatus;
use DomainException;

/**
 * Levée par WorkItem aggregate quand une transition workflow est invalide
 * (ex `paid` → `draft`, `billed` → `validated`).
 *
 * EPIC-003 Phase 3 (sprint-021 US-099 + US-101 Workflow Symfony state
 * machine 4 états).
 */
final class WorkItemInvalidTransitionException extends DomainException
{
    public function __construct(
        public readonly WorkItemId $workItemId,
        public readonly WorkItemStatus $currentStatus,
        public readonly WorkItemStatus $targetStatus,
    ) {
        parent::__construct(sprintf(
            'Invalid WorkItem transition: %s → %s for WorkItem %s',
            $currentStatus->value,
            $targetStatus->value,
            $workItemId,
        ));
    }
}

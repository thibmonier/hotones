<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\ValueObject;

/**
 * EPIC-003 Phase 3 (sprint-021 US-099 + US-101) — états workflow WorkItem.
 *
 * 4 états ADR-0016 Q3.1 : draft → validated → billed → paid.
 *
 * Transitions :
 * - validate : draft → validated (US-099 role-based managers Q3.2)
 * - bill : validated → billed (US-101 cross-aggregate Invoice listener)
 * - mark_paid : billed → paid (US-101 cross-aggregate Invoice listener)
 */
enum WorkItemStatus: string
{
    case DRAFT = 'draft';
    case VALIDATED = 'validated';
    case BILLED = 'billed';
    case PAID = 'paid';

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isValidated(): bool
    {
        return $this === self::VALIDATED;
    }

    public function isBilled(): bool
    {
        return $this === self::BILLED;
    }

    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ([$this, $target]) {
            [self::DRAFT, self::VALIDATED],
            [self::VALIDATED, self::BILLED],
            [self::BILLED, self::PAID] => true,
            default => false,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Vacation\ValueObject;

use App\Domain\Vacation\Exception\InvalidStatusTransitionException;

enum VacationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::APPROVED, self::REJECTED, self::CANCELLED],
            // APPROVED -> CANCELLED is allowed for manager-initiated cancellations
            // (US-069). The contributor's own cancel is restricted to PENDING by
            // VacationRequestController so the rule is purely additive.
            self::APPROVED => [self::CANCELLED],
            self::REJECTED => [],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function transitionTo(self $target): self
    {
        if (!$this->canTransitionTo($target)) {
            throw InvalidStatusTransitionException::create($this, $target);
        }

        return $target;
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::APPROVED => 'Approuve',
            self::REJECTED => 'Rejete',
            self::CANCELLED => 'Annule',
        };
    }
}

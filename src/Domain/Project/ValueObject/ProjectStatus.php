<?php

declare(strict_types=1);

namespace App\Domain\Project\ValueObject;

/**
 * Project status enum with state machine transitions.
 */
enum ProjectStatus: string
{
    case DRAFT     = 'draft';
    case ACTIVE    = 'active';
    case ON_HOLD   = 'on_hold';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Get allowed transitions from current status.
     *
     * @return ProjectStatus[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT     => [self::ACTIVE, self::CANCELLED],
            self::ACTIVE    => [self::ON_HOLD, self::COMPLETED, self::CANCELLED],
            self::ON_HOLD   => [self::ACTIVE, self::CANCELLED],
            self::COMPLETED => [],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isClosed(): bool
    {
        return $this === self::COMPLETED || $this === self::CANCELLED;
    }

    public function isOnHold(): bool
    {
        return $this === self::ON_HOLD;
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Brouillon',
            self::ACTIVE    => 'Actif',
            self::ON_HOLD   => 'En pause',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }
}

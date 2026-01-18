<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

/**
 * User account status with state machine transitions.
 */
enum UserStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case DEACTIVATED = 'deactivated';

    /**
     * Check if this status can transition to another status.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }

    /**
     * Get all allowed transitions from current status.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::ACTIVE, self::DEACTIVATED],
            self::ACTIVE => [self::SUSPENDED, self::DEACTIVATED],
            self::SUSPENDED => [self::ACTIVE, self::DEACTIVATED],
            self::DEACTIVATED => [], // Terminal state
        };
    }

    /**
     * Check if user can log in with this status.
     */
    public function canLogin(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if this is a terminal state.
     */
    public function isTerminal(): bool
    {
        return $this === self::DEACTIVATED;
    }

    /**
     * Check if this status is active.
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if this status is suspended.
     */
    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }

    /**
     * Check if this status is pending.
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Get the human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::ACTIVE => 'Actif',
            self::SUSPENDED => 'Suspendu',
            self::DEACTIVATED => 'Désactivé',
        };
    }
}

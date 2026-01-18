<?php

declare(strict_types=1);

namespace App\Domain\Company\ValueObject;

/**
 * Enum representing Company status with state machine logic.
 */
enum CompanyStatus: string
{
    case ACTIVE = 'active';
    case TRIAL = 'trial';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';

    /**
     * Returns allowed transitions from this status.
     *
     * @return array<CompanyStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::TRIAL => [self::ACTIVE, self::SUSPENDED, self::CANCELLED],
            self::ACTIVE => [self::SUSPENDED, self::CANCELLED],
            self::SUSPENDED => [self::ACTIVE, self::CANCELLED],
            self::CANCELLED => [], // Terminal state
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

    public function isTrial(): bool
    {
        return $this === self::TRIAL;
    }

    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function isOperational(): bool
    {
        return $this === self::ACTIVE || $this === self::TRIAL;
    }
}

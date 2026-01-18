<?php

declare(strict_types=1);

namespace App\Domain\Company\ValueObject;

/**
 * Enum representing subscription tier levels.
 */
enum SubscriptionTier: string
{
    case STARTER = 'starter';
    case PROFESSIONAL = 'professional';
    case ENTERPRISE = 'enterprise';

    public function isStarter(): bool
    {
        return $this === self::STARTER;
    }

    public function isProfessional(): bool
    {
        return $this === self::PROFESSIONAL;
    }

    public function isEnterprise(): bool
    {
        return $this === self::ENTERPRISE;
    }

    /**
     * Check if this tier includes a specific tier's features.
     */
    public function includes(self $tier): bool
    {
        return match ($this) {
            self::ENTERPRISE => true,
            self::PROFESSIONAL => $tier !== self::ENTERPRISE,
            self::STARTER => $tier === self::STARTER,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::STARTER => 'Starter',
            self::PROFESSIONAL => 'Professional',
            self::ENTERPRISE => 'Enterprise',
        };
    }
}

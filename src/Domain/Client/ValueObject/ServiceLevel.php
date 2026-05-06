<?php

declare(strict_types=1);

namespace App\Domain\Client\ValueObject;

enum ServiceLevel: string
{
    case STANDARD = 'standard';
    case PREMIUM = 'premium';
    case ENTERPRISE = 'enterprise';

    public function getLabel(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard',
            self::PREMIUM => 'Premium',
            self::ENTERPRISE => 'Enterprise',
        };
    }

    public function isPremiumOrHigher(): bool
    {
        return $this === self::PREMIUM || $this === self::ENTERPRISE;
    }
}

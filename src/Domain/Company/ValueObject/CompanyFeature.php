<?php

declare(strict_types=1);

namespace App\Domain\Company\ValueObject;

/**
 * Enum representing available company features.
 */
enum CompanyFeature: string
{
    case INVOICING = 'invoicing';
    case PLANNING = 'planning';
    case ANALYTICS = 'analytics';
    case BUSINESS_UNITS = 'business_units';
    case AI_TOOLS = 'ai_tools';
    case API_ACCESS = 'api_access';

    public function getLabel(): string
    {
        return match ($this) {
            self::INVOICING => 'Invoicing',
            self::PLANNING => 'Planning',
            self::ANALYTICS => 'Analytics',
            self::BUSINESS_UNITS => 'Business Units',
            self::AI_TOOLS => 'AI Tools',
            self::API_ACCESS => 'API Access',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::INVOICING => 'Generate and manage invoices',
            self::PLANNING => 'Project planning and scheduling',
            self::ANALYTICS => 'Advanced analytics and reporting',
            self::BUSINESS_UNITS => 'Multi-unit organization structure',
            self::AI_TOOLS => 'AI-powered assistance tools',
            self::API_ACCESS => 'External API access',
        };
    }

    /**
     * Get default features for a subscription tier.
     *
     * @return array<self>
     */
    public static function getDefaultsForTier(SubscriptionTier $tier): array
    {
        return match ($tier) {
            SubscriptionTier::STARTER => [self::INVOICING, self::PLANNING],
            SubscriptionTier::PROFESSIONAL => [
                self::INVOICING,
                self::PLANNING,
                self::ANALYTICS,
                self::BUSINESS_UNITS,
            ],
            SubscriptionTier::ENTERPRISE => self::cases(),
        };
    }
}

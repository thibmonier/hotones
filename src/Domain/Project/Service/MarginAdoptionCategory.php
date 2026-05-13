<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

/**
 * Margin adoption classification buckets (US-112 ADR-0013 KPI #3).
 *
 * Évalue la fraîcheur du snapshot marge d'un projet actif :
 * - Fresh           : margin_calculated_at < 7 jours (adoption MVP active)
 * - Stale warning   : 7 jours ≤ margin_calculated_at < 30 jours
 * - Stale critical  : margin_calculated_at NULL ou > 30 jours (dette d'usage)
 */
enum MarginAdoptionCategory: string
{
    case Fresh = 'fresh';
    case StaleWarning = 'stale_warning';
    case StaleCritical = 'stale_critical';

    public function label(): string
    {
        return match ($this) {
            self::Fresh => 'Frais (< 7 j)',
            self::StaleWarning => 'Tiède (7-30 j)',
            self::StaleCritical => 'Stale (> 30 j ou jamais calculé)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Fresh => 'success',
            self::StaleWarning => 'warning',
            self::StaleCritical => 'danger',
        };
    }
}

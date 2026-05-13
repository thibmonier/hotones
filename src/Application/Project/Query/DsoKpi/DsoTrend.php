<?php

declare(strict_types=1);

namespace App\Application\Project\Query\DsoKpi;

/**
 * DSO trend indicator vs previous rolling period (US-110 AC `tendance`).
 */
enum DsoTrend: string
{
    case Up = 'up';        // ↗️ DSO increased vs previous period (worse)
    case Down = 'down';    // ↘️ DSO decreased (better)
    case Stable = 'stable'; // → no significant change

    /**
     * Returns the emoji glyph displayed on the dashboard widget.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::Up => '↗️',
            self::Down => '↘️',
            self::Stable => '→',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Up => 'En hausse vs période précédente',
            self::Down => 'En baisse vs période précédente',
            self::Stable => 'Stable vs période précédente',
        };
    }
}

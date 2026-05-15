<?php

declare(strict_types=1);

namespace App\Domain\Project\ValueObject;

/**
 * Tendance taux de conversion vs fenêtre précédente (US-115).
 * Pattern aligné DsoTrend (US-110).
 */
enum ConversionTrend: string
{
    case Up = 'up';
    case Down = 'down';
    case Stable = 'stable';

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
            self::Up => 'en hausse',
            self::Down => 'en baisse',
            self::Stable => 'stable',
        };
    }
}

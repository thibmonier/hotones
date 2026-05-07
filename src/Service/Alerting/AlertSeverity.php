<?php

declare(strict_types=1);

namespace App\Service\Alerting;

enum AlertSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';

    public function emoji(): string
    {
        return match ($this) {
            self::INFO => ':information_source:',
            self::WARNING => ':warning:',
            self::ERROR => ':rotating_light:',
            self::CRITICAL => ':fire:',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INFO => '#36a64f',     // green
            self::WARNING => '#ff9933',  // orange
            self::ERROR => '#cc0000',    // red
            self::CRITICAL => '#660000', // dark red
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message pour déclencher la génération des prévisions de CA.
 */
final readonly class GenerateForecastsMessage
{
    public function __construct(
        private int $months = 12
    ) {
    }

    public function getMonths(): int
    {
        return $this->months;
    }
}

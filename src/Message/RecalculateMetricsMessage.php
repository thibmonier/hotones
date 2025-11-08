<?php

declare(strict_types=1);

namespace App\Message;

final readonly class RecalculateMetricsMessage
{
    public function __construct(
        public string $date,
        public string $granularity,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message pour declencher une synchronisation asynchrone des temps BoondManager.
 */
final readonly class SyncBoondManagerTimesMessage
{
    public function __construct(
        public int $companyId,
        public string $startDate,
        public string $endDate,
    ) {
    }
}

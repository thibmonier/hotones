<?php

declare(strict_types=1);

namespace App\Application\Vacation\Query\GetPendingVacationsForManager;

final readonly class GetPendingVacationsForManagerQuery
{
    public function __construct(
        public int $managerUserId,
    ) {
    }
}

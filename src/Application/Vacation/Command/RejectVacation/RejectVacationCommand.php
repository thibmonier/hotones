<?php

declare(strict_types=1);

namespace App\Application\Vacation\Command\RejectVacation;

final readonly class RejectVacationCommand
{
    public function __construct(
        public string $vacationId,
    ) {
    }
}

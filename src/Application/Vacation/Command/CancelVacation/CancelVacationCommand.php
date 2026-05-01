<?php

declare(strict_types=1);

namespace App\Application\Vacation\Command\CancelVacation;

final readonly class CancelVacationCommand
{
    public function __construct(
        public string $vacationId,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Vacation\Command\ApproveVacation;

final readonly class ApproveVacationCommand
{
    public function __construct(
        public string $vacationId,
        public int $approvedByUserId,
    ) {
    }
}

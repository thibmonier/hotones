<?php

declare(strict_types=1);

namespace App\Application\Vacation\Query\CountApprovedDays;

use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CountApprovedDaysHandler
{
    public function __construct(
        private VacationRepositoryInterface $vacationRepository,
    ) {
    }

    public function __invoke(CountApprovedDaysQuery $query): float
    {
        return $this->vacationRepository->countApprovedDaysBetween(
            $query->startDate,
            $query->endDate,
        );
    }
}

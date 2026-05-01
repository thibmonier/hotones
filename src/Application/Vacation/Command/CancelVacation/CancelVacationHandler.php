<?php

declare(strict_types=1);

namespace App\Application\Vacation\Command\CancelVacation;

use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CancelVacationHandler
{
    public function __construct(
        private VacationRepositoryInterface $vacationRepository,
    ) {
    }

    public function __invoke(CancelVacationCommand $command): void
    {
        $vacation = $this->vacationRepository->findById(
            VacationId::fromString($command->vacationId),
        );

        $vacation->cancel();
        $this->vacationRepository->save($vacation);
    }
}

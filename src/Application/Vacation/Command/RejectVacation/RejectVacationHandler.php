<?php

declare(strict_types=1);

namespace App\Application\Vacation\Command\RejectVacation;

use App\Application\Vacation\Notification\Message\VacationNotificationMessage;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class RejectVacationHandler
{
    public function __construct(
        private VacationRepositoryInterface $vacationRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(RejectVacationCommand $command): void
    {
        $vacation = $this->vacationRepository->findById(VacationId::fromString($command->vacationId));

        $vacation->reject($command->rejectionReason);
        $this->vacationRepository->save($vacation);

        $this->messageBus->dispatch(new VacationNotificationMessage($command->vacationId, 'rejected'));
    }
}

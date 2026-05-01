<?php

declare(strict_types=1);

namespace App\Application\Vacation\Command\CancelVacation;

use App\Application\Vacation\Notification\Message\VacationNotificationMessage;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class CancelVacationHandler
{
    public function __construct(
        private VacationRepositoryInterface $vacationRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(CancelVacationCommand $command): void
    {
        $vacation = $this->vacationRepository->findById(
            VacationId::fromString($command->vacationId),
        );

        $vacation->cancel();
        $this->vacationRepository->save($vacation);

        // TECH-DEBT-001: dispatch a notification message so the right party gets told.
        // - cancelled-by-manager : the manager just cancelled an APPROVED or PENDING request,
        //   intervenant must be notified (was previously silent).
        // - cancelled : the intervenant cancelled their own PENDING request,
        //   the manager is notified to keep the team view in sync.
        $type = $command->isManagerInitiated() ? 'cancelled-by-manager' : 'cancelled';

        $this->messageBus->dispatch(
            new VacationNotificationMessage($command->vacationId, $type),
        );
    }
}

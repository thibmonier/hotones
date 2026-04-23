<?php

declare(strict_types=1);

namespace App\Application\Vacation\Command\ApproveVacation;

use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Infrastructure\Vacation\Notification\Message\VacationNotificationMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ApproveVacationHandler
{
    public function __construct(
        private VacationRepositoryInterface $vacationRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(ApproveVacationCommand $command): void
    {
        $vacation = $this->vacationRepository->findById(
            VacationId::fromString($command->vacationId),
        );

        $approvedBy = $this->entityManager->getReference(
            \App\Entity\User::class,
            $command->approvedByUserId,
        );

        $vacation->approve($approvedBy);
        $this->vacationRepository->save($vacation);

        $this->messageBus->dispatch(
            new VacationNotificationMessage($command->vacationId, 'approved'),
        );
    }
}

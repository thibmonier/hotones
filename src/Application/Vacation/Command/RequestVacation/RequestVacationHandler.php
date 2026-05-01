<?php

declare(strict_types=1);

namespace App\Application\Vacation\Command\RequestVacation;

use App\Application\Vacation\Notification\Message\VacationNotificationMessage;
use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\DailyHours;
use App\Domain\Vacation\ValueObject\DateRange;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Domain\Vacation\ValueObject\VacationType;
use App\Repository\ContributorRepository;
use InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class RequestVacationHandler
{
    public function __construct(
        private VacationRepositoryInterface $vacationRepository,
        private ContributorRepository $contributorRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(RequestVacationCommand $command): VacationId
    {
        $contributor = $this->contributorRepository->find($command->contributorId);

        if ($contributor === null) {
            throw new InvalidArgumentException('Contributor not found');
        }

        $vacationId = VacationId::generate();

        $vacation = Vacation::request(
            id: $vacationId,
            company: $contributor->getCompany(),
            contributor: $contributor,
            dateRange: DateRange::create($command->startDate, $command->endDate),
            type: VacationType::from($command->type),
            dailyHours: DailyHours::fromString($command->dailyHours),
            reason: $command->reason,
        );

        $this->vacationRepository->save($vacation);

        $this->messageBus->dispatch(
            new VacationNotificationMessage($vacationId->getValue(), 'created'),
        );

        return $vacationId;
    }
}

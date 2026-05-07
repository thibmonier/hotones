<?php

declare(strict_types=1);

namespace App\Application\Vacation\Query\GetPendingVacationsForManager;

use App\Application\Vacation\DTO\VacationDTO;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Repository\ContributorRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetPendingVacationsForManagerHandler
{
    public function __construct(
        private VacationRepositoryInterface $vacationRepository,
        private ContributorRepository $contributorRepository,
    ) {
    }

    /**
     * @return VacationDTO[]
     */
    public function __invoke(GetPendingVacationsForManagerQuery $query): array
    {
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $query->managerUserId]);

        if ($managerContributor === null) {
            return [];
        }

        $managedContributors = $managerContributor->getManagedContributors();

        $vacations = $this->vacationRepository->findPendingForContributors($managedContributors->toArray());

        return array_map(static fn ($vacation): VacationDTO => VacationDTO::fromEntity($vacation), $vacations);
    }
}

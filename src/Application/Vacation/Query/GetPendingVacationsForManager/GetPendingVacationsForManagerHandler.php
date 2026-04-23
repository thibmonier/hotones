<?php

declare(strict_types=1);

namespace App\Application\Vacation\Query\GetPendingVacationsForManager;

use App\Domain\Vacation\Entity\Vacation;
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
     * @return Vacation[]
     */
    public function __invoke(GetPendingVacationsForManagerQuery $query): array
    {
        $managerContributor = $this->contributorRepository->findOneBy(['user' => $query->managerUserId]);

        if ($managerContributor === null) {
            return [];
        }

        $managedContributors = $managerContributor->getManagedContributors();

        return $this->vacationRepository->findPendingForContributors(
            $managedContributors->toArray(),
        );
    }
}

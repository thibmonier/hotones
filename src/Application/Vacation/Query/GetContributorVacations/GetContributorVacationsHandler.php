<?php

declare(strict_types=1);

namespace App\Application\Vacation\Query\GetContributorVacations;

use App\Application\Vacation\DTO\VacationDTO;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Repository\ContributorRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetContributorVacationsHandler
{
    public function __construct(
        private VacationRepositoryInterface $vacationRepository,
        private ContributorRepository $contributorRepository,
    ) {
    }

    /**
     * @return VacationDTO[]
     */
    public function __invoke(GetContributorVacationsQuery $query): array
    {
        $contributor = $this->contributorRepository->find($query->contributorId);

        if ($contributor === null) {
            return [];
        }

        $vacations = $this->vacationRepository->findByContributor($contributor);

        return array_map(static fn ($vacation) => VacationDTO::fromEntity($vacation), $vacations);
    }
}

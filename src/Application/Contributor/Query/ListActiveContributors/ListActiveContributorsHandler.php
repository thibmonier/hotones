<?php

declare(strict_types=1);

namespace App\Application\Contributor\Query\ListActiveContributors;

use App\Domain\Contributor\Repository\ContributorRepositoryInterface;

/**
 * Sprint-018 Phase 3 strangler fig Contributor BC.
 *
 * Délègue la lecture à `ContributorRepositoryInterface::findActive()` (DDD)
 * via `DoctrineDddContributorRepository` (ACL Phase 2 active).
 *
 * @see ADR-0008 ACL pattern
 */
final readonly class ListActiveContributorsHandler
{
    public function __construct(
        private ContributorRepositoryInterface $contributorRepository,
    ) {
    }

    /**
     * @return array<ContributorListItemDto>
     */
    public function __invoke(ListActiveContributorsQuery $query): array
    {
        $contributors = $this->contributorRepository->findActive();

        return array_map(
            ContributorListItemDto::fromAggregate(...),
            $contributors,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Vacation\Repository;

use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Exception\VacationNotFoundException;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Entity\Contributor;
use DateTimeInterface;

interface VacationRepositoryInterface
{
    /**
     * @throws VacationNotFoundException
     */
    public function findById(VacationId $id): Vacation;

    public function findByIdOrNull(VacationId $id): ?Vacation;

    /**
     * @return Vacation[]
     */
    public function findByContributor(Contributor $contributor): array;

    /**
     * @param Contributor[] $contributors
     *
     * @return Vacation[]
     */
    public function findPendingForContributors(array $contributors): array;

    public function countApprovedDaysBetween(DateTimeInterface $startDate, DateTimeInterface $endDate): float;

    public function save(Vacation $vacation): void;
}

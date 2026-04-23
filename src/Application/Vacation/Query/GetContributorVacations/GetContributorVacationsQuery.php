<?php

declare(strict_types=1);

namespace App\Application\Vacation\Query\GetContributorVacations;

final readonly class GetContributorVacationsQuery
{
    public function __construct(
        public int $contributorId,
    ) {
    }
}

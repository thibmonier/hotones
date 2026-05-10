<?php

declare(strict_types=1);

namespace App\Domain\EmploymentPeriod\Exception;

use App\Domain\Contributor\ValueObject\ContributorId;
use DateTimeImmutable;
use DomainException;

/**
 * Levée par DailyHoursValidator quand aucun EmploymentPeriod actif trouvé
 * pour (contributor, date).
 *
 * EPIC-003 Phase 3 ADR-0016 + ADR-0015.
 */
final class NoActiveEmploymentPeriodException extends DomainException
{
    public function __construct(
        public readonly ContributorId $contributorId,
        public readonly DateTimeImmutable $date,
    ) {
        parent::__construct(sprintf(
            'No active EmploymentPeriod found for contributor %s on date %s',
            $contributorId,
            $date->format('Y-m-d'),
        ));
    }
}

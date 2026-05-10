<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\Exception;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use DateTimeImmutable;
use DomainException;

/**
 * Levée par UC RecordWorkItem quand dailyTotal + additionalHours > dailyMaxHours
 * et command.userOverride === false.
 *
 * Non bloquante : ADR-0016 Q2.4 décision "warning + override user". UI capture
 * exception et propose checkbox confirmation override.
 *
 * Si command.userOverride === true → exception NON levée (override accepté +
 * audit log enregistré).
 *
 * EPIC-003 Phase 3 ADR-0015 invariant journalier + ADR-0016 Q2.4.
 */
final class DailyHoursWarningException extends DomainException
{
    public function __construct(
        public readonly ContributorId $contributorId,
        public readonly DateTimeImmutable $date,
        public readonly WorkedHours $dailyTotal,
        public readonly WorkedHours $dailyMaxHours,
    ) {
        parent::__construct(sprintf(
            'Daily hours warning for contributor %s on %s: total %.2fh exceeds max %.2fh (override available via command.userOverride=true)',
            $contributorId,
            $date->format('Y-m-d'),
            $dailyTotal->getValue(),
            $dailyMaxHours->getValue(),
        ));
    }
}

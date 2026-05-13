<?php

declare(strict_types=1);

namespace App\Infrastructure\WorkItem\Migration;

use App\Domain\WorkItem\Migration\HourlyRateProviderInterface;
use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine adapter for {@see HourlyRateProviderInterface} (US-113 T-113-03).
 *
 * Résolution rate au workDate par étapes :
 *   1. Charger Contributor par id
 *   2. Chercher EmploymentPeriod dont [startDate, endDate?] contient workDate
 *      → utiliser `EmploymentPeriod.cjm` historique
 *   3. Fallback : `Contributor.cjm` direct (property hook PHP 8.4 résout
 *      automatiquement EmploymentPeriod actif courant)
 *
 * Conversion : cjm (décimal € journalier) → cents/heure = cjm × 100 / 8
 * (HOURS_PER_DAY = 8 cohérent avec {@see App\Domain\WorkItem\ValueObject\HourlyRate}).
 *
 * Retourne null si aucune source disponible — le Migrator flagge alors
 * `missingRate` et skip le timesheet (legacy_no_rate hint Q3 audit mitigation).
 */
final readonly class DoctrineHourlyRateProvider implements HourlyRateProviderInterface
{
    private const int HOURS_PER_DAY = 8;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function resolveAt(int $contributorId, DateTimeImmutable $workDate): ?int
    {
        $contributor = $this->entityManager->find(Contributor::class, $contributorId);
        if ($contributor === null) {
            return null;
        }

        $cjmAtDate = $this->findCjmAtDate($contributor, $workDate)
            ?? $contributor->cjm; // property hook fallback

        if ($cjmAtDate === null || trim($cjmAtDate) === '') {
            return null;
        }

        $cjm = (float) $cjmAtDate;
        if ($cjm <= 0.0) {
            return null;
        }

        // cents/heure = (cjm € / 8h) × 100 cents = cjm × 12.5 cents
        return (int) round($cjm * 100 / self::HOURS_PER_DAY);
    }

    private function findCjmAtDate(Contributor $contributor, DateTimeImmutable $workDate): ?string
    {
        $periods = $this->entityManager->getRepository(EmploymentPeriod::class)
            ->findBy(['contributor' => $contributor]);

        foreach ($periods as $period) {
            $start = $period->startDate ?? null;
            $end = $period->endDate ?? null;

            if ($start === null) {
                continue;
            }

            if ($workDate < DateTimeImmutable::createFromInterface($start)) {
                continue;
            }

            if ($end !== null && $workDate > DateTimeImmutable::createFromInterface($end)) {
                continue;
            }

            return $period->cjm;
        }

        return null;
    }
}

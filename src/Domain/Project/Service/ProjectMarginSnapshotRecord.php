<?php

declare(strict_types=1);

namespace App\Domain\Project\Service;

use DateTimeImmutable;

/**
 * Read-model DTO for margin adoption calculation (US-112).
 *
 * Représente un projet actif (status != archived) avec sa date de dernier
 * snapshot marge. Hydraté par le Repository depuis la projection
 * `Project.marginCalculatedAt`.
 *
 * Domain pure — pas de référence Doctrine.
 */
final readonly class ProjectMarginSnapshotRecord
{
    public function __construct(
        public int $projectId,
        public string $projectName,
        public ?DateTimeImmutable $marginCalculatedAt,
    ) {
    }

    public function ageInDays(DateTimeImmutable $now): ?float
    {
        if ($this->marginCalculatedAt === null) {
            return null;
        }

        $secondsPerDay = 86_400;
        $diff = $now->getTimestamp() - $this->marginCalculatedAt->getTimestamp();

        return $diff / $secondsPerDay;
    }
}

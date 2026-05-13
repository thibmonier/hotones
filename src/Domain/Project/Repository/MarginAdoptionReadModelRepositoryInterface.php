<?php

declare(strict_types=1);

namespace App\Domain\Project\Repository;

use App\Domain\Project\Service\ProjectMarginSnapshotRecord;

/**
 * Read-model repository for margin adoption KPI (US-112).
 *
 * Provides projection of active projects with their last `marginCalculatedAt`
 * snapshot, consumed by {@see App\Domain\Project\Service\MarginAdoptionCalculator}.
 *
 * Multi-tenant aware: implementations MUST filter by current company.
 */
interface MarginAdoptionReadModelRepositoryInterface
{
    /**
     * Return all active (status='active') projects of the current company
     * with their latest margin snapshot timestamp.
     *
     * Projects with NULL `marginCalculatedAt` are included (classified as
     * "stale critical" downstream).
     *
     * @return list<ProjectMarginSnapshotRecord>
     */
    public function findActiveWithMarginSnapshot(): array;
}

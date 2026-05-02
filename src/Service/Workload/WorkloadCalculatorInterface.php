<?php

declare(strict_types=1);

namespace App\Service\Workload;

use DateTimeImmutable;

/**
 * Computes per-contributor workload metrics for a given month.
 *
 * Extracted from `AlertDetectionService::calculateContributorWorkload`
 * (TEST-WORKLOAD-001, sprint-005) so the alert pipeline can be unit-tested
 * with a mocked calculator instead of mocking a Doctrine QueryBuilder
 * end-to-end.
 */
interface WorkloadCalculatorInterface
{
    /**
     * @return array{totalDays: float, capacityRate: float}
     */
    public function forContributor(int $contributorId, DateTimeImmutable $month): array;
}

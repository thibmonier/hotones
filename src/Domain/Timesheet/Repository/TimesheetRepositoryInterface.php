<?php

declare(strict_types=1);

namespace App\Domain\Timesheet\Repository;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Timesheet\Entity\Timesheet;
use App\Domain\Timesheet\Exception\TimesheetNotFoundException;
use App\Domain\Timesheet\ValueObject\TimesheetId;
use DateTimeImmutable;

/**
 * Repository interface for Timesheet aggregate root.
 */
interface TimesheetRepositoryInterface
{
    /**
     * Find a timesheet entry by ID.
     *
     * @throws TimesheetNotFoundException
     */
    public function findById(TimesheetId $id): Timesheet;

    /**
     * Find a timesheet entry by ID, or null if not found.
     */
    public function findByIdOrNull(TimesheetId $id): ?Timesheet;

    /**
     * Find all timesheet entries for a company.
     *
     * @return Timesheet[]
     */
    public function findByCompany(CompanyId $companyId): array;

    /**
     * Find timesheet entries for a contributor.
     *
     * @return Timesheet[]
     */
    public function findByContributor(ContributorId $contributorId): array;

    /**
     * Find timesheet entries for a contributor within a date range.
     *
     * @return Timesheet[]
     */
    public function findByContributorAndDateRange(
        ContributorId $contributorId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
    ): array;

    /**
     * Find timesheet entries for a project.
     *
     * @return Timesheet[]
     */
    public function findByProject(ProjectId $projectId): array;

    /**
     * Find timesheet entries for a project within a date range.
     *
     * @return Timesheet[]
     */
    public function findByProjectAndDateRange(
        ProjectId $projectId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
    ): array;

    /**
     * Find timesheet entries for a specific date.
     *
     * @return Timesheet[]
     */
    public function findByDate(CompanyId $companyId, DateTimeImmutable $date): array;

    /**
     * Find timesheet entries for a contributor on a specific date.
     *
     * @return Timesheet[]
     */
    public function findByContributorAndDate(
        ContributorId $contributorId,
        DateTimeImmutable $date,
    ): array;

    /**
     * Find existing entry for contributor, project, and date (duplicate check).
     */
    public function findByContributorProjectAndDate(
        ContributorId $contributorId,
        ProjectId $projectId,
        DateTimeImmutable $date,
    ): ?Timesheet;

    /**
     * Calculate total hours for a contributor within a date range.
     */
    public function sumHoursByContributorAndDateRange(
        ContributorId $contributorId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
    ): float;

    /**
     * Calculate total hours for a project within a date range.
     */
    public function sumHoursByProjectAndDateRange(
        ProjectId $projectId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
    ): float;

    /**
     * Count timesheet entries for a company.
     */
    public function countByCompany(CompanyId $companyId): int;

    /**
     * Save a timesheet entry (create or update).
     */
    public function save(Timesheet $timesheet): void;

    /**
     * Delete a timesheet entry.
     */
    public function delete(Timesheet $timesheet): void;
}

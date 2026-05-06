<?php

declare(strict_types=1);

namespace App\Domain\Project\Repository;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Entity\Project;
use App\Domain\Project\Exception\ProjectNotFoundException;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectStatus;
use App\Domain\Project\ValueObject\ProjectType;

/**
 * Repository interface for Project aggregate root.
 *
 * Implementations should be in Infrastructure layer.
 */
interface ProjectRepositoryInterface
{
    /**
     * Find a project by its ID.
     *
     * @throws ProjectNotFoundException if the project does not exist
     */
    public function findById(ProjectId $id): Project;

    /**
     * Find a project by its ID, returning null if not found.
     */
    public function findByIdOrNull(ProjectId $id): ?Project;

    /**
     * Find a project by its reference.
     */
    public function findByReference(string $reference): ?Project;

    /**
     * Find all projects for a specific client.
     *
     * @return Project[]
     */
    public function findByClientId(ClientId $clientId): array;

    /**
     * Find all projects with a specific status.
     *
     * @return Project[]
     */
    public function findByStatus(ProjectStatus $status): array;

    /**
     * Find all projects of a specific type.
     *
     * @return Project[]
     */
    public function findByType(ProjectType $type): array;

    /**
     * Find all active projects.
     *
     * @return Project[]
     */
    public function findActive(): array;

    /**
     * Find all internal projects.
     *
     * @return Project[]
     */
    public function findInternal(): array;

    /**
     * Find all projects.
     *
     * @return Project[]
     */
    public function findAll(): array;

    /**
     * Persist a project.
     */
    public function save(Project $project): void;

    /**
     * Remove a project.
     */
    public function delete(Project $project): void;
}

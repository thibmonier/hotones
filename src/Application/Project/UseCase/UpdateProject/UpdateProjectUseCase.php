<?php

declare(strict_types=1);

namespace App\Application\Project\UseCase\UpdateProject;

use App\Domain\Project\Repository\ProjectRepositoryInterface;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectStatus;
use InvalidArgumentException;

/**
 * Update Project via DDD aggregate (read-modify-save through ACL).
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 */
final readonly class UpdateProjectUseCase
{
    public function __construct(
        private ProjectRepositoryInterface $repository,
    ) {
    }

    public function execute(UpdateProjectCommand $command): void
    {
        $id = ProjectId::fromLegacyInt($command->projectId);
        $project = $this->repository->findById($id);

        $project->updateDetails($command->name, $command->description, $command->reference);

        if ($command->status !== null) {
            $project->changeStatus($this->parseStatus($command->status));
        }

        $this->repository->save($project);
    }

    private function parseStatus(string $raw): ProjectStatus
    {
        return match (strtolower($raw)) {
            'draft' => ProjectStatus::DRAFT,
            'active' => ProjectStatus::ACTIVE,
            'on_hold' => ProjectStatus::ON_HOLD,
            'completed' => ProjectStatus::COMPLETED,
            'cancelled' => ProjectStatus::CANCELLED,
            default => throw new InvalidArgumentException(sprintf('Unknown project status: %s', $raw)),
        };
    }
}

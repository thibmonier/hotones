<?php

declare(strict_types=1);

namespace App\Domain\Project\Event;

use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectStatus;
use App\Domain\Shared\Interface\DomainEventInterface;
use DateTimeImmutable;

final readonly class ProjectStatusChangedEvent implements DomainEventInterface
{
    public function __construct(
        private ProjectId $projectId,
        private ProjectStatus $previousStatus,
        private ProjectStatus $newStatus,
        private DateTimeImmutable $occurredOn,
    ) {
    }

    public static function create(ProjectId $projectId, ProjectStatus $previousStatus, ProjectStatus $newStatus): self
    {
        return new self($projectId, $previousStatus, $newStatus, new DateTimeImmutable());
    }

    public function getProjectId(): ProjectId
    {
        return $this->projectId;
    }

    public function getPreviousStatus(): ProjectStatus
    {
        return $this->previousStatus;
    }

    public function getNewStatus(): ProjectStatus
    {
        return $this->newStatus;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Project\Event;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Shared\Interface\DomainEventInterface;

final readonly class ProjectCreatedEvent implements DomainEventInterface
{
    public function __construct(
        private ProjectId $projectId,
        private ClientId $clientId,
        private string $name,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public static function create(ProjectId $projectId, ClientId $clientId, string $name): self
    {
        return new self($projectId, $clientId, $name, new \DateTimeImmutable());
    }

    public function getProjectId(): ProjectId
    {
        return $this->projectId;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Project\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Event\ProjectCreatedEvent;
use App\Domain\Project\Event\ProjectStatusChangedEvent;
use App\Domain\Project\Exception\InvalidProjectStatusTransitionException;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectStatus;
use App\Domain\Project\ValueObject\ProjectType;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Trait\RecordsDomainEvents;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use InvalidArgumentException;

final class Project implements AggregateRootInterface
{
    use RecordsDomainEvents;

    private ProjectId $id;
    private string $name;
    private ?string $description;
    private ClientId $clientId;
    private ProjectStatus $status;
    private ProjectType $projectType;
    private bool $isInternal;
    private ?Money $budget;
    private ?Money $soldAmount;
    private ?string $reference;
    private ?DateTimeImmutable $startDate;
    private ?DateTimeImmutable $endDate;
    private ?DateTimeImmutable $completedAt;
    private ?string $repositoryUrl;
    private ?string $documentationUrl;
    private ?string $notes;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    private function __construct(
        ProjectId $id,
        string $name,
        ClientId $clientId,
        ProjectType $projectType,
        bool $isInternal = false,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->clientId = $clientId;
        $this->projectType = $projectType;
        $this->isInternal = $isInternal;
        $this->status = ProjectStatus::DRAFT;
        $this->description = null;
        $this->budget = null;
        $this->soldAmount = null;
        $this->reference = null;
        $this->startDate = null;
        $this->endDate = null;
        $this->completedAt = null;
        $this->repositoryUrl = null;
        $this->documentationUrl = null;
        $this->notes = null;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(
        ProjectId $id,
        string $name,
        ClientId $clientId,
        ProjectType $projectType,
        bool $isInternal = false,
    ): self {
        $project = new self($id, $name, $clientId, $projectType, $isInternal);

        $project->recordEvent(
            ProjectCreatedEvent::create($id, $clientId, $name),
        );

        return $project;
    }

    public function updateDetails(
        string $name,
        ?string $description,
        ?string $reference,
    ): void {
        $this->name = $name;
        $this->description = $description;
        $this->reference = $reference;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setDates(
        ?DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate,
    ): void {
        if ($startDate !== null && $endDate !== null && $startDate > $endDate) {
            throw new InvalidArgumentException('Start date cannot be after end date');
        }

        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setBudget(?Money $budget): void
    {
        $this->budget = $budget;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setSoldAmount(?Money $soldAmount): void
    {
        $this->soldAmount = $soldAmount;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setTechnicalInfo(
        ?string $repositoryUrl,
        ?string $documentationUrl,
    ): void {
        $this->repositoryUrl = $repositoryUrl;
        $this->documentationUrl = $documentationUrl;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addNotes(string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function changeStatus(ProjectStatus $newStatus): void
    {
        if ($this->status === $newStatus) {
            return;
        }

        if (!$this->status->canTransitionTo($newStatus)) {
            throw InvalidProjectStatusTransitionException::create($this->status, $newStatus);
        }

        $previousStatus = $this->status;
        $this->status = $newStatus;
        $this->updatedAt = new DateTimeImmutable();

        if ($newStatus === ProjectStatus::COMPLETED) {
            $this->completedAt = new DateTimeImmutable();
        }

        $this->recordEvent(
            ProjectStatusChangedEvent::create($this->id, $previousStatus, $newStatus),
        );
    }

    public function activate(): void
    {
        $this->changeStatus(ProjectStatus::ACTIVE);
    }

    public function complete(): void
    {
        $this->changeStatus(ProjectStatus::COMPLETED);
    }

    public function cancel(): void
    {
        $this->changeStatus(ProjectStatus::CANCELLED);
    }

    public function putOnHold(): void
    {
        $this->changeStatus(ProjectStatus::ON_HOLD);
    }

    // Calculated values

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    public function isFixedPrice(): bool
    {
        return $this->projectType->isFixedPrice();
    }

    public function isTimeAndMaterials(): bool
    {
        return $this->projectType->isTimeAndMaterials();
    }

    public function getDurationDays(): ?int
    {
        if ($this->startDate === null || $this->endDate === null) {
            return null;
        }

        return $this->startDate->diff($this->endDate)->days;
    }

    // Getters

    public function getId(): ProjectId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function getProjectType(): ProjectType
    {
        return $this->projectType;
    }

    public function isInternal(): bool
    {
        return $this->isInternal;
    }

    public function getBudget(): ?Money
    {
        return $this->budget;
    }

    public function getSoldAmount(): ?Money
    {
        return $this->soldAmount;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getStartDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getRepositoryUrl(): ?string
    {
        return $this->repositoryUrl;
    }

    public function getDocumentationUrl(): ?string
    {
        return $this->documentationUrl;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

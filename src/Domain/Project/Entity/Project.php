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
    private ?string $description;
    private ProjectStatus $status;
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

    /**
     * EPIC-003 Phase 3 (sprint-022 US-104 ADR-0016 Q4.x) — snapshot marge
     * calculée par UC `CalculateProjectMargin`. Transient (non persisté
     * sprint-022 — migration sprint-023+ si demande PO de persistence).
     */
    private ?Money $coutTotal = null;
    private ?Money $factureTotal = null;
    private ?DateTimeImmutable $margeCalculatedAt = null;

    private function __construct(
        private ProjectId $id,
        private string $name,
        private ClientId $clientId,
        private ProjectType $projectType,
        private bool $isInternal = false,
    ) {
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

        $project->recordEvent(ProjectCreatedEvent::create($id, $clientId, $name));

        return $project;
    }

    /**
     * Reconstitute an aggregate from persisted state — used by ACL adapters
     * during EPIC-001 Phase 2 strangler fig. Does NOT record domain events.
     *
     * @param array<string, mixed> $extra
     */
    public static function reconstitute(
        ProjectId $id,
        string $name,
        ClientId $clientId,
        ProjectType $projectType,
        bool $isInternal,
        array $extra = [],
    ): self {
        $project = new self($id, $name, $clientId, $projectType, $isInternal);

        if (isset($extra['status']) && $extra['status'] instanceof ProjectStatus) {
            $project->status = $extra['status'];
        }
        $project->description = $extra['description'] ?? null;
        $project->reference = $extra['reference'] ?? null;
        $project->budget = $extra['budget'] ?? null;
        $project->soldAmount = $extra['soldAmount'] ?? null;
        $project->startDate = $extra['startDate'] ?? null;
        $project->endDate = $extra['endDate'] ?? null;
        $project->completedAt = $extra['completedAt'] ?? null;
        $project->repositoryUrl = $extra['repositoryUrl'] ?? null;
        $project->documentationUrl = $extra['documentationUrl'] ?? null;
        $project->notes = $extra['notes'] ?? null;

        if (isset($extra['createdAt']) && $extra['createdAt'] instanceof DateTimeImmutable) {
            $project->createdAt = $extra['createdAt'];
        }
        $project->updatedAt = $extra['updatedAt'] ?? null;

        // EPIC-003 Phase 3 (sprint-023 US-107) — restore margin snapshot
        // persisté via ACL translator
        if (isset($extra['coutTotal']) && $extra['coutTotal'] instanceof Money) {
            $project->coutTotal = $extra['coutTotal'];
        }
        if (isset($extra['factureTotal']) && $extra['factureTotal'] instanceof Money) {
            $project->factureTotal = $extra['factureTotal'];
        }
        if (isset($extra['margeCalculatedAt']) && $extra['margeCalculatedAt'] instanceof DateTimeImmutable) {
            $project->margeCalculatedAt = $extra['margeCalculatedAt'];
        }

        return $project;
    }

    public function updateDetails(string $name, ?string $description, ?string $reference): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->reference = $reference;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setDates(?DateTimeImmutable $startDate, ?DateTimeImmutable $endDate): void
    {
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

    public function setTechnicalInfo(?string $repositoryUrl, ?string $documentationUrl): void
    {
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

        $this->recordEvent(ProjectStatusChangedEvent::create($this->id, $previousStatus, $newStatus));
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

    /**
     * EPIC-003 Phase 3 (sprint-022 US-104) — calcule snapshot marge depuis
     * coût total (sum WorkItem.cost) + facturé payé total (sum Invoice.paid).
     *
     * Caller (UC CalculateProjectMargin Application Layer) résout les sums
     * via repositories puis appelle cette méthode pour figer le snapshot.
     */
    public function setMargeSnapshot(Money $coutTotal, Money $factureTotal): void
    {
        $this->coutTotal = $coutTotal;
        $this->factureTotal = $factureTotal;
        $this->margeCalculatedAt = new DateTimeImmutable();
    }

    public function getCoutTotal(): ?Money
    {
        return $this->coutTotal;
    }

    public function getFactureTotal(): ?Money
    {
        return $this->factureTotal;
    }

    /**
     * Marge absolue en centimes : factureTotal - coutTotal (peut être négatif).
     * Null si snapshot pas calculé.
     *
     * Retourne int (centimes) au lieu de Money car Money n'autorise pas
     * négatif (invariant Money strict positive). Pour affichage UI :
     * `$marge / 100.0` donne euros (signed).
     */
    public function getMargeAbsoluteCents(): ?int
    {
        if ($this->factureTotal === null || $this->coutTotal === null) {
            return null;
        }

        return $this->factureTotal->getAmountCents() - $this->coutTotal->getAmountCents();
    }

    /**
     * Marge en pourcentage : (margeAbsolute / factureTotal) × 100.
     * Null si factureTotal = 0 OR snapshot pas calculé.
     */
    public function getMargePercent(): ?float
    {
        if ($this->factureTotal === null || $this->coutTotal === null) {
            return null;
        }

        if ($this->factureTotal->isZero()) {
            return null;
        }

        $margeCents = $this->getMargeAbsoluteCents();
        if ($margeCents === null) {
            return null;
        }

        return ($margeCents / $this->factureTotal->getAmountCents()) * 100.0;
    }

    public function getMargeCalculatedAt(): ?DateTimeImmutable
    {
        return $this->margeCalculatedAt;
    }

    /**
     * Indique si la marge actuelle est sous le seuil donné.
     * Retourne false si snapshot pas calculé OR factureTotal = 0.
     */
    public function hasMargeBelowThreshold(float $thresholdPercent): bool
    {
        $marge = $this->getMargePercent();
        if ($marge === null) {
            return false;
        }

        return $marge < $thresholdPercent;
    }
}

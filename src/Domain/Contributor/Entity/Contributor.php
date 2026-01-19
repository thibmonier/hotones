<?php

declare(strict_types=1);

namespace App\Domain\Contributor\Entity;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\Event\ContributorActivatedEvent;
use App\Domain\Contributor\Event\ContributorCreatedEvent;
use App\Domain\Contributor\Event\ContributorDeactivatedEvent;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Contributor\ValueObject\Gender;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Trait\RecordsDomainEvents;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;

/**
 * Contributor aggregate root - represents a worker/employee within a company (multi-tenant).
 */
final class Contributor implements AggregateRootInterface
{
    use RecordsDomainEvents;

    private ContributorId $id;
    private CompanyId $companyId;
    private string $firstName;
    private string $lastName;
    private Email $email;
    private ?string $phonePersonal;
    private ?string $phoneProfessional;
    private ?DateTimeImmutable $birthDate;
    private Gender $gender;
    private ?string $address;
    private ?string $avatarFilename;
    private ?string $notes;
    private ?float $cjm; // Cost per day (Coût Journalier Moyen)
    private ?float $tjm; // Rate per day (Taux Journalier Moyen)
    private bool $active;

    // Linked user account (optional)
    private ?UserId $userId;

    // Manager (self-referencing)
    private ?ContributorId $managerId;

    // Timestamps
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    private function __construct(
        ContributorId $id,
        CompanyId $companyId,
        string $firstName,
        string $lastName,
        Email $email,
    ) {
        $this->id        = $id;
        $this->companyId = $companyId;
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->email     = $email;

        // Default values
        $this->phonePersonal     = null;
        $this->phoneProfessional = null;
        $this->birthDate         = null;
        $this->gender            = Gender::NOT_SPECIFIED;
        $this->address           = null;
        $this->avatarFilename    = null;
        $this->notes             = null;
        $this->cjm               = null;
        $this->tjm               = null;
        $this->active            = true;
        $this->userId            = null;
        $this->managerId         = null;

        // Timestamps
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(
        ContributorId $id,
        CompanyId $companyId,
        string $firstName,
        string $lastName,
        Email $email,
    ): self {
        $contributor = new self($id, $companyId, $firstName, $lastName, $email);

        $contributor->recordEvent(
            ContributorCreatedEvent::create($id, $companyId, $email, $firstName, $lastName),
        );

        return $contributor;
    }

    // Profile management

    public function updateProfile(
        string $firstName,
        string $lastName,
        ?string $phonePersonal = null,
        ?string $phoneProfessional = null,
        ?DateTimeImmutable $birthDate = null,
        ?Gender $gender = null,
        ?string $address = null,
    ): void {
        $this->firstName         = $firstName;
        $this->lastName          = $lastName;
        $this->phonePersonal     = $phonePersonal;
        $this->phoneProfessional = $phoneProfessional;
        $this->birthDate         = $birthDate;
        $this->gender            = $gender ?? $this->gender;
        $this->address           = $address;
        $this->updatedAt         = new DateTimeImmutable();
    }

    public function updateEmail(Email $newEmail): void
    {
        $this->email     = $newEmail;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateAvatar(?string $filename): void
    {
        $this->avatarFilename = $filename;
        $this->updatedAt      = new DateTimeImmutable();
    }

    public function updateNotes(?string $notes): void
    {
        $this->notes     = $notes;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Work-related management

    public function updateRates(?float $cjm, ?float $tjm): void
    {
        $this->cjm       = $cjm;
        $this->tjm       = $tjm;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function assignManager(?ContributorId $managerId): void
    {
        $this->managerId = $managerId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function linkUser(UserId $userId): void
    {
        $this->userId    = $userId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function unlinkUser(): void
    {
        $this->userId    = null;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Activation status management

    public function activate(): void
    {
        if ($this->active) {
            return;
        }

        $this->active    = true;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(ContributorActivatedEvent::create($this->id));
    }

    public function deactivate(): void
    {
        if (!$this->active) {
            return;
        }

        $this->active    = false;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(ContributorDeactivatedEvent::create($this->id));
    }

    // Calculated values

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    public function getAge(): ?int
    {
        if ($this->birthDate === null) {
            return null;
        }

        return $this->birthDate->diff(new DateTimeImmutable())->y;
    }

    public function hasLinkedUser(): bool
    {
        return $this->userId !== null;
    }

    public function hasManager(): bool
    {
        return $this->managerId !== null;
    }

    // Getters

    public function getId(): ContributorId
    {
        return $this->id;
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPhonePersonal(): ?string
    {
        return $this->phonePersonal;
    }

    public function getPhoneProfessional(): ?string
    {
        return $this->phoneProfessional;
    }

    public function getBirthDate(): ?DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function getGender(): Gender
    {
        return $this->gender;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getAvatarFilename(): ?string
    {
        return $this->avatarFilename;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCjm(): ?float
    {
        return $this->cjm;
    }

    public function getTjm(): ?float
    {
        return $this->tjm;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getUserId(): ?UserId
    {
        return $this->userId;
    }

    public function getManagerId(): ?ContributorId
    {
        return $this->managerId;
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

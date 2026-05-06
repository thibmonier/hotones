<?php

declare(strict_types=1);

namespace App\Domain\Client\Entity;

use App\Domain\Client\Event\ClientCreatedEvent;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\ValueObject\CompanyName;
use App\Domain\Client\ValueObject\ServiceLevel;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Trait\RecordsDomainEvents;
use App\Domain\Shared\ValueObject\Email;
use DateTimeImmutable;

final class Client implements AggregateRootInterface
{
    use RecordsDomainEvents;

    private ClientId $id;
    private CompanyName $name;
    private ?Email $email;
    private ?string $phone;
    private ?string $address;
    private ?string $city;
    private ?string $postalCode;
    private ?string $country;
    private ?string $vatNumber;
    private ServiceLevel $serviceLevel;
    private bool $isActive;
    private ?string $notes;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    private function __construct(
        ClientId $id,
        CompanyName $name,
        ServiceLevel $serviceLevel,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->serviceLevel = $serviceLevel;
        $this->isActive = true;
        $this->email = null;
        $this->phone = null;
        $this->address = null;
        $this->city = null;
        $this->postalCode = null;
        $this->country = null;
        $this->vatNumber = null;
        $this->notes = null;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
    }

    public static function create(
        ClientId $id,
        CompanyName $name,
        ServiceLevel $serviceLevel = ServiceLevel::STANDARD,
    ): self {
        $client = new self($id, $name, $serviceLevel);

        $client->recordEvent(
            ClientCreatedEvent::create($id, $name->getValue()),
        );

        return $client;
    }

    /**
     * Reconstitute an aggregate from persisted state — used by ACL adapters
     * during EPIC-001 Phase 2 strangler fig. Does NOT record a domain event
     * (the entity already exists in the legacy store).
     *
     * @param array{
     *     email?: ?Email,
     *     phone?: ?string,
     *     address?: ?string,
     *     city?: ?string,
     *     postalCode?: ?string,
     *     country?: ?string,
     *     vatNumber?: ?string,
     *     isActive?: bool,
     *     notes?: ?string,
     *     createdAt?: DateTimeImmutable,
     *     updatedAt?: ?DateTimeImmutable,
     * } $extra
     */
    public static function reconstitute(
        ClientId $id,
        CompanyName $name,
        ServiceLevel $serviceLevel,
        array $extra = [],
    ): self {
        $client = new self($id, $name, $serviceLevel);

        if (isset($extra['email'])) {
            $client->email = $extra['email'];
        }
        $client->phone = $extra['phone'] ?? null;
        $client->address = $extra['address'] ?? null;
        $client->city = $extra['city'] ?? null;
        $client->postalCode = $extra['postalCode'] ?? null;
        $client->country = $extra['country'] ?? null;
        $client->vatNumber = $extra['vatNumber'] ?? null;
        $client->isActive = $extra['isActive'] ?? true;
        $client->notes = $extra['notes'] ?? null;

        if (isset($extra['createdAt'])) {
            $client->createdAt = $extra['createdAt'];
        }
        $client->updatedAt = $extra['updatedAt'] ?? null;

        return $client;
    }

    public function updateContactInfo(
        ?Email $email,
        ?string $phone,
        ?string $address,
        ?string $city,
        ?string $postalCode,
        ?string $country,
    ): void {
        $this->email = $email;
        $this->phone = $phone;
        $this->address = $address;
        $this->city = $city;
        $this->postalCode = $postalCode;
        $this->country = $country;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateServiceLevel(ServiceLevel $serviceLevel): void
    {
        if ($this->serviceLevel === $serviceLevel) {
            return;
        }

        $this->serviceLevel = $serviceLevel;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateVatNumber(?string $vatNumber): void
    {
        $this->vatNumber = $vatNumber;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function rename(CompanyName $name): void
    {
        if ($this->name->equals($name)) {
            return;
        }

        $this->name = $name;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function activate(): void
    {
        if ($this->isActive) {
            return;
        }

        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        if (!$this->isActive) {
            return;
        }

        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addNotes(string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters

    public function getId(): ClientId
    {
        return $this->id;
    }

    public function getName(): CompanyName
    {
        return $this->name;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function getServiceLevel(): ServiceLevel
    {
        return $this->serviceLevel;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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

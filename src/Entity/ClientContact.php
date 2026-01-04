<?php

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\ClientContactRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientContactRepository::class)]
#[ORM\Table(name: 'client_contacts')]
#[ORM\Index(name: 'idx_client_contact_company', columns: ['company_id'])]
class ClientContact implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    public Company $company {
        get => $this->company;
        set {
            $this->company = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(type: 'string', length: 100)]
    public string $lastName = '' {
        get => $this->lastName;
        set {
            $this->lastName = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 100)]
    public string $firstName = '' {
        get => $this->firstName;
        set {
            $this->firstName = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    public ?string $email = null {
        get => $this->email;
        set {
            $this->email = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    public ?string $phone = null {
        get => $this->phone;
        set {
            $this->phone = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    public ?string $mobilePhone = null {
        get => $this->mobilePhone;
        set {
            $this->mobilePhone = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    public ?string $positionTitle = null {
        get => $this->positionTitle;
        set {
            $this->positionTitle = $value;
        }
    }

    #[ORM\Column(type: 'boolean')]
    public bool $active = true {
        get => $this->active;
        set {
            $this->active = $value;
        }
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 public private(set), prefer direct access: $contact->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->lastName.
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->lastName = $value.
     */
    public function setLastName(string $value): self
    {
        $this->lastName = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->firstName.
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->firstName = $value.
     */
    public function setFirstName(string $value): self
    {
        $this->firstName = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->email.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->email = $value.
     */
    public function setEmail(?string $value): self
    {
        $this->email = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->phone.
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->phone = $value.
     */
    public function setPhone(?string $value): self
    {
        $this->phone = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->mobilePhone.
     */
    public function getMobilePhone(): ?string
    {
        return $this->mobilePhone;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->mobilePhone = $value.
     */
    public function setMobilePhone(?string $value): self
    {
        $this->mobilePhone = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->positionTitle.
     */
    public function getPositionTitle(): ?string
    {
        return $this->positionTitle;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->positionTitle = $value.
     */
    public function setPositionTitle(?string $value): self
    {
        $this->positionTitle = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->active.
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $contact->active = $value.
     */
    public function setActive(bool $value): self
    {
        $this->active = $value;

        return $this;
    }
}

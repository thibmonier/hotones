<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'clients')]
#[ORM\Index(name: 'idx_client_company', columns: ['company_id'])]
class Client implements Stringable, CompanyOwnedInterface
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

    #[ORM\Column(type: 'string', length: 180)]
    public string $name = '' {
        get => $this->name;
        set {
            $this->name = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $logoPath = null {
        get => $this->logoPath;
        set {
            $this->logoPath = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $website = null {
        get => $this->website;
        set {
            $this->website = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null {
        get => $this->description;
        set {
            $this->description = $value;
        }
    }

    // Service Level: vip, priority, standard, low
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    public ?string $serviceLevel = null {
        get => $this->serviceLevel;
        set {
            $this->serviceLevel = $value;
        }
    }

    // Service Level Mode: auto (calculé automatiquement) ou manual (défini manuellement)
    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'auto'])]
    public string $serviceLevelMode = 'auto' {
        get => $this->serviceLevelMode;
        set {
            $this->serviceLevelMode = $value;
        }
    }

    #[ORM\OneToMany(
        mappedBy: 'client',
        targetEntity: ClientContact::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $contacts;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
    }

    /** @return Collection<int, ClientContact> */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(ClientContact $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
            $contact->setClient($this);
        }

        return $this;
    }

    public function removeContact(ClientContact $contact): self
    {
        if ($this->contacts->removeElement($contact)) {
            if ($contact->getClient() === $this) {
                $contact->setClient(null);
            }
        }

        return $this;
    }

    public function getServiceLevelLabel(): string
    {
        return match ($this->serviceLevel) {
            'vip'      => 'VIP',
            'priority' => 'Prioritaire',
            'standard' => 'Standard',
            'low'      => 'Basse priorité',
            default    => 'Non défini',
        };
    }

    public function getServiceLevelBadgeClass(): string
    {
        return match ($this->serviceLevel) {
            'vip'      => 'bg-danger',
            'priority' => 'bg-warning',
            'standard' => 'bg-info',
            'low'      => 'bg-secondary',
            default    => 'bg-light text-dark',
        };
    }

    public function __toString(): string
    {
        return $this->name;
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
     * With PHP 8.4 property hooks, prefer direct access: $client->name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $client->name = $value.
     */
    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $client->logoPath.
     */
    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $client->logoPath = $value.
     */
    public function setLogoPath(?string $value): self
    {
        $this->logoPath = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $client->website.
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $client->website = $value.
     */
    public function setWebsite(?string $value): self
    {
        $this->website = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $client->description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $client->description = $value.
     */
    public function setDescription(?string $value): self
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $client->serviceLevel.
     */
    public function getServiceLevel(): ?string
    {
        return $this->serviceLevel;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $client->serviceLevel = $value.
     */
    public function setServiceLevel(?string $value): self
    {
        $this->serviceLevel = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $client->serviceLevelMode.
     */
    public function getServiceLevelMode(): string
    {
        return $this->serviceLevelMode;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $client->serviceLevelMode = $value.
     */
    public function setServiceLevelMode(string $value): self
    {
        $this->serviceLevelMode = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 public private(set), prefer direct access: $client->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}

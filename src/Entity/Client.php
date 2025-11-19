<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'clients')]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $logoPath = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // Service Level: vip, priority, standard, low
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $serviceLevel = null;

    // Service Level Mode: auto (calculé automatiquement) ou manual (défini manuellement)
    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'auto'])]
    private string $serviceLevelMode = 'auto';

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: ClientContact::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $contacts;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): self
    {
        $this->logoPath = $logoPath;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
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

    public function getServiceLevel(): ?string
    {
        return $this->serviceLevel;
    }

    public function setServiceLevel(?string $serviceLevel): self
    {
        $this->serviceLevel = $serviceLevel;

        return $this;
    }

    public function getServiceLevelMode(): string
    {
        return $this->serviceLevelMode;
    }

    public function setServiceLevelMode(string $serviceLevelMode): self
    {
        $this->serviceLevelMode = $serviceLevelMode;

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

    public function __toString()
    {
        return $this->name;
    }
}

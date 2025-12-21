<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SaasProviderRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fournisseur de services SaaS (ex: Google, Microsoft, Adobe, Stripe, etc.).
 */
#[ORM\Entity(repositoryClass: SaasProviderRepository::class)]
#[ORM\Table(name: 'saas_providers')]
#[ORM\HasLifecycleCallbacks]
class SaasProvider
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom du fournisseur.
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name = '';

    /**
     * Site web du fournisseur.
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $website = null;

    /**
     * Email de contact du fournisseur.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $contactEmail = null;

    /**
     * Téléphone de contact du fournisseur.
     */
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $contactPhone = null;

    /**
     * Notes internes sur le fournisseur.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * Actif ou non.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $active = true;

    /**
     * Logo du fournisseur (URL ou chemin).
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $logoUrl = null;

    /**
     * Date de création.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $createdAt = null;

    /**
     * Date de dernière modification.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    /**
     * Services proposés par ce fournisseur.
     *
     * @var Collection<int, SaasService>
     */
    #[ORM\OneToMany(targetEntity: SaasService::class, mappedBy: 'provider')]
    private Collection $services;

    public function __construct()
    {
        $this->services  = new ArrayCollection();
        $this->createdAt = new DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTime();
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

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): self
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function setLogoUrl(?string $logoUrl): self
    {
        $this->logoUrl = $logoUrl;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, SaasService>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(SaasService $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setProvider($this);
        }

        return $this;
    }

    public function removeService(SaasService $service): self
    {
        if ($this->services->removeElement($service)) {
            if ($service->getProvider() === $this) {
                $service->setProvider(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

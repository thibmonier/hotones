<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SaasServiceRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Service SaaS proposé (ex: Google Workspace, Slack Premium, GitHub Team, etc.).
 */
#[ORM\Entity(repositoryClass: SaasServiceRepository::class)]
#[ORM\Table(name: 'saas_services')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['provider_id'], name: 'idx_saas_service_provider')]
class SaasService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom du service.
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name = '';

    /**
     * Description du service.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Fournisseur du service (peut être null si souscription directe).
     */
    #[ORM\ManyToOne(targetEntity: SaasProvider::class, inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SaasProvider $provider = null;

    /**
     * Catégorie du service (ex: Communication, Productivité, Développement, etc.).
     */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $category = null;

    /**
     * URL du service.
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $serviceUrl = null;

    /**
     * Logo du service (URL ou chemin).
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $logoUrl = null;

    /**
     * Prix mensuel par défaut.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $defaultMonthlyPrice = null;

    /**
     * Prix annuel par défaut.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $defaultYearlyPrice = null;

    /**
     * Devise (EUR, USD, etc.).
     */
    #[ORM\Column(type: Types::STRING, length: 3, options: ['default' => 'EUR'])]
    private string $currency = 'EUR';

    /**
     * Notes internes sur le service.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * Actif ou non.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $active = true;

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
     * Abonnements à ce service.
     *
     * @var Collection<int, SaasSubscription>
     */
    #[ORM\OneToMany(targetEntity: SaasSubscription::class, mappedBy: 'service')]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->subscriptions = new ArrayCollection();
        $this->createdAt     = new DateTime();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getProvider(): ?SaasProvider
    {
        return $this->provider;
    }

    public function setProvider(?SaasProvider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getServiceUrl(): ?string
    {
        return $this->serviceUrl;
    }

    public function setServiceUrl(?string $serviceUrl): self
    {
        $this->serviceUrl = $serviceUrl;

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

    public function getDefaultMonthlyPrice(): ?string
    {
        return $this->defaultMonthlyPrice;
    }

    public function setDefaultMonthlyPrice(?string $defaultMonthlyPrice): self
    {
        $this->defaultMonthlyPrice = $defaultMonthlyPrice;

        return $this;
    }

    public function getDefaultYearlyPrice(): ?string
    {
        return $this->defaultYearlyPrice;
    }

    public function setDefaultYearlyPrice(?string $defaultYearlyPrice): self
    {
        $this->defaultYearlyPrice = $defaultYearlyPrice;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

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
     * @return Collection<int, SaasSubscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(SaasSubscription $subscription): self
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setService($this);
        }

        return $this;
    }

    public function removeSubscription(SaasSubscription $subscription): self
    {
        if ($this->subscriptions->removeElement($subscription)) {
            if ($subscription->getService() === $this) {
                $subscription->setService(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name.($this->provider ? ' ('.$this->provider->getName().')' : '');
    }
}

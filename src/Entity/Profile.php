<?php

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\ProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ORM\Table(name: 'profiles')]
#[ORM\Index(name: 'idx_profile_company', columns: ['company_id'])]
class Profile implements CompanyOwnedInterface
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

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    public string $name {
        get => $this->name;
        set {
            $this->name = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null {
        get => $this->description;
        set {
            $this->description = $value;
        }
    }

    // TJM standard pour ce profil (peut être surchargé par projet/client)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $defaultDailyRate = null {
        get => $this->defaultDailyRate;
        set {
            $this->defaultDailyRate = $value;
        }
    }

    // CJM (Coût Journalier Moyen) pour ce profil
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $cjm = null {
        get => $this->cjm;
        set {
            $this->cjm = $value;
        }
    }

    // Coefficient d'ajustement de marge (par défaut 1.0)
    // Permet d'ajuster le CJM pour le calcul de coût estimé
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true, options: ['default' => '1.00'])]
    public ?string $marginCoefficient = '1.00' {
        get => $this->marginCoefficient;
        set {
            $this->marginCoefficient = $value;
        }
    }

    // Couleur pour l'affichage dans les graphiques
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    public ?string $color = null {
        get => $this->color;
        set {
            $this->color = $value;
        }
    }

    #[ORM\Column(type: 'boolean')]
    public bool $active = true {
        get => $this->active;
        set {
            $this->active = $value;
        }
    }

    // Relation avec les contributeurs (Many-to-Many)
    #[ORM\ManyToMany(targetEntity: Contributor::class, mappedBy: 'profiles')]
    private Collection $contributors;

    // Relation avec les tâches des devis
    #[ORM\OneToMany(mappedBy: 'profile', targetEntity: OrderTask::class)]
    private Collection $orderTasks;

    public function __construct()
    {
        $this->contributors = new ArrayCollection();
        $this->orderTasks   = new ArrayCollection();
    }

    // Collection methods
    public function getContributors(): Collection
    {
        return $this->contributors;
    }

    public function addContributor(Contributor $contributor): self
    {
        if (!$this->contributors->contains($contributor)) {
            $this->contributors[] = $contributor;
            $contributor->addProfile($this);
        }

        return $this;
    }

    public function removeContributor(Contributor $contributor): self
    {
        if ($this->contributors->removeElement($contributor)) {
            $contributor->removeProfile($this);
        }

        return $this;
    }

    public function getOrderTasks(): Collection
    {
        return $this->orderTasks;
    }

    public function addOrderTask(OrderTask $orderTask): self
    {
        if (!$this->orderTasks->contains($orderTask)) {
            $this->orderTasks[] = $orderTask;
            $orderTask->setProfile($this);
        }

        return $this;
    }

    public function removeOrderTask(OrderTask $orderTask): self
    {
        if ($this->orderTasks->removeElement($orderTask)) {
            if ($orderTask->getProfile() === $this) {
                $orderTask->setProfile(null);
            }
        }

        return $this;
    }

    // CompanyOwnedInterface implementation
    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    // Compatibility methods for existing code
    // With PHP 8.4 property hooks, prefer direct property access where possible

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 public private(set), prefer direct access: $profile->id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->name = $value.
     */
    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->description = $value.
     */
    public function setDescription(?string $value): self
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->defaultDailyRate.
     */
    public function getDefaultDailyRate(): ?string
    {
        return $this->defaultDailyRate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->defaultDailyRate = $value.
     */
    public function setDefaultDailyRate(?string $value): self
    {
        $this->defaultDailyRate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->cjm.
     */
    public function getCjm(): ?string
    {
        return $this->cjm;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->cjm = $value.
     */
    public function setCjm(?string $value): self
    {
        $this->cjm = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->marginCoefficient.
     */
    public function getMarginCoefficient(): ?string
    {
        return $this->marginCoefficient;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->marginCoefficient = $value.
     */
    public function setMarginCoefficient(?string $value): self
    {
        $this->marginCoefficient = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->color.
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->color = $value.
     */
    public function setColor(?string $value): self
    {
        $this->color = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->active.
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $profile->active = $value.
     */
    public function setActive(bool $value): self
    {
        $this->active = $value;

        return $this;
    }
}

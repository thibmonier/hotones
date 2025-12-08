<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ORM\Table(name: 'profiles')]
class Profile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // TJM standard pour ce profil (peut être surchargé par projet/client)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $defaultDailyRate = null;

    // CJM (Coût Journalier Moyen) pour ce profil
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $cjm = null;

    // Coefficient d'ajustement de marge (par défaut 1.0)
    // Permet d'ajuster le CJM pour le calcul de coût estimé
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true, options: ['default' => '1.00'])]
    private ?string $marginCoefficient = '1.00';

    // Couleur pour l'affichage dans les graphiques
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

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

    public function getDefaultDailyRate(): ?string
    {
        return $this->defaultDailyRate;
    }

    public function setDefaultDailyRate(?string $defaultDailyRate): self
    {
        $this->defaultDailyRate = $defaultDailyRate;

        return $this;
    }

    public function getCjm(): ?string
    {
        return $this->cjm;
    }

    public function setCjm(?string $cjm): self
    {
        $this->cjm = $cjm;

        return $this;
    }

    public function getMarginCoefficient(): ?string
    {
        return $this->marginCoefficient;
    }

    public function setMarginCoefficient(?string $marginCoefficient): self
    {
        $this->marginCoefficient = $marginCoefficient;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

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
}

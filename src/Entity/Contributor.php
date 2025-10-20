<?php

namespace App\Entity;

use App\Repository\ContributorRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ContributorRepository::class)]
#[ORM\Table(name: 'contributors')]
class Contributor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    // Coût Journalier Moyen (coût réel)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $cjm = null;

    // Tarif Journalier Moyen (prix de vente)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $tjm = null;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    // Relation avec l'utilisateur (optionnel)
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    // Profils multiples du contributeur (dev, lead dev, chef projet, etc.)
    #[ORM\ManyToMany(targetEntity: Profile::class, inversedBy: 'contributors')]
    #[ORM\JoinTable(name: 'contributor_profiles')]
    private Collection $profiles;

    // Périodes d'emploi du contributeur
    #[ORM\OneToMany(targetEntity: EmploymentPeriod::class, mappedBy: 'contributor', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['startDate' => 'DESC'])]
    private Collection $employmentPeriods;

    // Temps passés par le contributeur
    #[ORM\OneToMany(targetEntity: Timesheet::class, mappedBy: 'contributor')]
    private Collection $timesheets;

    public function __construct()
    {
        $this->profiles = new ArrayCollection();
        $this->employmentPeriods = new ArrayCollection();
        $this->timesheets = new ArrayCollection();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
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

    public function getCjm(): ?string
    {
        return $this->cjm;
    }
    public function setCjm(?string $cjm): self
    {
        $this->cjm = $cjm;
        return $this;
    }

    public function getTjm(): ?string
    {
        return $this->tjm;
    }
    public function setTjm(?string $tjm): self
    {
        $this->tjm = $tjm;
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

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getProfiles(): Collection
    {
        return $this->profiles;
    }
    public function addProfile(Profile $profile): self
    {
        if (!$this->profiles->contains($profile)) {
            $this->profiles[] = $profile;
        }
        return $this;
    }
    public function removeProfile(Profile $profile): self
    {
        $this->profiles->removeElement($profile);
        return $this;
    }

    public function getEmploymentPeriods(): Collection
    {
        return $this->employmentPeriods;
    }
    public function addEmploymentPeriod(EmploymentPeriod $employmentPeriod): self
    {
        if (!$this->employmentPeriods->contains($employmentPeriod)) {
            $this->employmentPeriods[] = $employmentPeriod;
            $employmentPeriod->setContributor($this);
        }
        return $this;
    }
    public function removeEmploymentPeriod(EmploymentPeriod $employmentPeriod): self
    {
        if ($this->employmentPeriods->removeElement($employmentPeriod)) {
            // set the owning side to null (unless already changed)
            if ($employmentPeriod->getContributor() === $this) {
                $employmentPeriod->setContributor(null);
            }
        }
        return $this;
    }

    public function getTimesheets(): Collection
    {
        return $this->timesheets;
    }
    public function addTimesheet(Timesheet $timesheet): self
    {
        if (!$this->timesheets->contains($timesheet)) {
            $this->timesheets[] = $timesheet;
            $timesheet->setContributor($this);
        }
        return $this;
    }
    public function removeTimesheet(Timesheet $timesheet): self
    {
        if ($this->timesheets->removeElement($timesheet)) {
            // set the owning side to null (unless already changed)
            if ($timesheet->getContributor() === $this) {
                $timesheet->setContributor(null);
            }
        }
        return $this;
    }

    // Méthode utilitaire pour obtenir les noms des profils
    public function getProfileNames(): array
    {
        return $this->profiles->map(fn (Profile $profile) => $profile->getName())->toArray();
    }
}

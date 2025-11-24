<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ContributorRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContributorRepository::class)]
#[ORM\Table(name: 'contributors')]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
    ],
    normalizationContext: ['groups' => ['contributor:read']],
    paginationItemsPerPage: 30,
)]
class Contributor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['contributor:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['contributor:read'])]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['contributor:read'])]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['contributor:read'])]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phonePersonal = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phoneProfessional = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $birthDate = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $gender = null; // 'male', 'female', 'other'

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatarFilename = null;

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

    // Manager responsable de ce contributeur
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'managedContributors')]
    #[ORM\JoinColumn(name: 'manager_id', nullable: true, onDelete: 'SET NULL')]
    private ?Contributor $manager = null;

    // Contributeurs gérés par ce manager
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'manager')]
    private Collection $managedContributors;

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

    // Compétences du contributeur avec niveaux
    #[ORM\OneToMany(targetEntity: ContributorSkill::class, mappedBy: 'contributor', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $contributorSkills;

    public function __construct()
    {
        $this->profiles            = new ArrayCollection();
        $this->employmentPeriods   = new ArrayCollection();
        $this->timesheets          = new ArrayCollection();
        $this->managedContributors = new ArrayCollection();
        $this->contributorSkills   = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    /**
     * Alias plus explicite pour le nom complet du contributeur.
     */
    public function getFullName(): string
    {
        return $this->getName();
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

    public function getPhonePersonal(): ?string
    {
        return $this->phonePersonal;
    }

    public function setPhonePersonal(?string $phonePersonal): self
    {
        $this->phonePersonal = $phonePersonal;

        return $this;
    }

    public function getPhoneProfessional(): ?string
    {
        return $this->phoneProfessional;
    }

    public function setPhoneProfessional(?string $phoneProfessional): self
    {
        $this->phoneProfessional = $phoneProfessional;

        return $this;
    }

    public function getBirthDate(): ?DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?DateTimeInterface $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    /**
     * Calcule l'âge du contributeur à partir de sa date de naissance.
     */
    public function getAge(?DateTimeInterface $referenceDate = null): ?int
    {
        if ($this->birthDate === null) {
            return null;
        }

        $reference = $referenceDate ?? new DateTime();
        $interval  = $this->birthDate->diff($reference);

        return $interval->y;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Retourne le libellé du genre en français.
     */
    public function getGenderLabel(): string
    {
        return match ($this->gender) {
            'male'   => 'Homme',
            'female' => 'Femme',
            'other'  => 'Autre',
            default  => 'Non renseigné',
        };
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getAvatarFilename(): ?string
    {
        return $this->avatarFilename;
    }

    public function setAvatarFilename(?string $avatarFilename): self
    {
        $this->avatarFilename = $avatarFilename;

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

    public function getManager(): ?Contributor
    {
        return $this->manager;
    }

    public function setManager(?Contributor $manager): self
    {
        $this->manager = $manager;

        return $this;
    }

    public function getManagedContributors(): Collection
    {
        return $this->managedContributors;
    }

    public function addManagedContributor(Contributor $contributor): self
    {
        if (!$this->managedContributors->contains($contributor)) {
            $this->managedContributors[] = $contributor;
            $contributor->setManager($this);
        }

        return $this;
    }

    public function removeManagedContributor(Contributor $contributor): self
    {
        if ($this->managedContributors->removeElement($contributor)) {
            // set the owning side to null (unless already changed)
            if ($contributor->getManager() === $this) {
                $contributor->setManager(null);
            }
        }

        return $this;
    }

    // Méthode utilitaire pour obtenir les noms des profils
    public function getProfileNames(): array
    {
        return $this->profiles->map(fn (Profile $profile) => $profile->getName())->toArray();
    }

    /**
     * Récupère la période d'emploi active (celle qui contient la date actuelle).
     * Si plusieurs périodes sont actives, retourne la plus récente.
     */
    public function getCurrentEmploymentPeriod(): ?EmploymentPeriod
    {
        $now = new DateTime();

        foreach ($this->employmentPeriods as $period) {
            if ($period->getStartDate() <= $now && ($period->getEndDate() === null || $period->getEndDate() >= $now)) {
                return $period;
            }
        }

        return null;
    }

    /**
     * Retourne le nombre d'heures hebdomadaires du contributeur basé sur sa période d'emploi active.
     */
    public function getWeeklyHours(): float
    {
        $period = $this->getCurrentEmploymentPeriod();
        if ($period) {
            return (float) $period->getWeeklyHours();
        }

        return 35.0; // Valeur par défaut
    }

    /**
     * Retourne le nombre d'heures par jour basé sur le temps de travail contractuel.
     * Calcul: heures hebdomadaires / jours travaillés par semaine
     * Exemples: 35h/5j = 7h/j, 32h/4j = 8h/j, 28h/4j = 7h/j.
     */
    public function getHoursPerDay(): float
    {
        $period = $this->getCurrentEmploymentPeriod();
        if ($period) {
            return $period->getHoursPerDay();
        }

        return 7.0; // Valeur par défaut pour 35h/5j
    }

    /**
     * @return Collection<int, ContributorSkill>
     */
    public function getContributorSkills(): Collection
    {
        return $this->contributorSkills;
    }

    public function addContributorSkill(ContributorSkill $contributorSkill): self
    {
        if (!$this->contributorSkills->contains($contributorSkill)) {
            $this->contributorSkills->add($contributorSkill);
            $contributorSkill->setContributor($this);
        }

        return $this;
    }

    public function removeContributorSkill(ContributorSkill $contributorSkill): self
    {
        if ($this->contributorSkills->removeElement($contributorSkill)) {
            if ($contributorSkill->getContributor() === $this) {
                $contributorSkill->setContributor(null);
            }
        }

        return $this;
    }

    /**
     * Retourne les compétences par catégorie.
     *
     * @return array<string, Collection<int, ContributorSkill>>
     */
    public function getSkillsByCategory(): array
    {
        $byCategory = [];
        foreach ($this->contributorSkills as $contributorSkill) {
            $category = $contributorSkill->getSkill()->getCategory();
            if (!isset($byCategory[$category])) {
                $byCategory[$category] = new ArrayCollection();
            }
            $byCategory[$category]->add($contributorSkill);
        }

        return $byCategory;
    }

    /**
     * Retourne le nombre total de compétences du contributeur.
     */
    public function getSkillsCount(): int
    {
        return $this->contributorSkills->count();
    }
}

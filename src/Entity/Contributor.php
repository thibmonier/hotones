<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\ContributorRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContributorRepository::class)]
#[ORM\Table(name: 'contributors')]
#[ORM\Index(name: 'idx_contributor_company', columns: ['company_id'])]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
    ],
    normalizationContext: ['groups' => ['contributor:read']],
    paginationItemsPerPage: 30,
)]
class Contributor implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['contributor:read'])]
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

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['contributor:read'])]
    public string $firstName {
        get => $this->firstName;
        set {
            $this->firstName = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['contributor:read'])]
    public string $lastName {
        get => $this->lastName;
        set {
            $this->lastName = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['contributor:read'])]
    public ?string $email = null {
        get => $this->email;
        set {
            $this->email = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    public ?string $phonePersonal = null {
        get => $this->phonePersonal;
        set {
            $this->phonePersonal = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    public ?string $phoneProfessional = null {
        get => $this->phoneProfessional;
        set {
            $this->phoneProfessional = $value;
        }
    }

    #[ORM\Column(type: 'date', nullable: true)]
    public ?DateTimeInterface $birthDate = null {
        get => $this->birthDate;
        set {
            $this->birthDate = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    public ?string $gender = null { // 'male', 'female', 'other'
        get => $this->gender;
        set {
            $this->gender = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $address = null {
        get => $this->address;
        set {
            $this->address = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $avatarFilename = null {
        get => $this->avatarFilename;
        set {
            $this->avatarFilename = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $notes = null {
        get => $this->notes;
        set {
            $this->notes = $value;
        }
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $cjm = null {
        get => $this->getRelevantEmploymentPeriod()?->getCjm() ?? $this->cjm;
        set {
            $this->cjm = $value;
        }
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $tjm = null {
        get => $this->getRelevantEmploymentPeriod()?->getTjm() ?? $this->tjm;
        set {
            $this->tjm = $value;
        }
    }

    #[ORM\Column(type: 'boolean')]
    public bool $active = true {
        get => $this->active;
        set {
            $this->active = $value;
        }
    }

    // Relation avec l'utilisateur (optionnel)
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?User $user = null {
        get => $this->user;
        set {
            $this->user = $value;
        }
    }

    // Manager responsable de ce contributeur
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'managedContributors')]
    #[ORM\JoinColumn(name: 'manager_id', nullable: true, onDelete: 'SET NULL')]
    public ?Contributor $manager = null {
        get => $this->manager;
        set {
            $this->manager = $value;
        }
    }

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

    /**
     * Récupère le salaire mensuel depuis la période d'emploi active ou la plus récente.
     */
    public function getSalary(): ?string
    {
        $period = $this->getRelevantEmploymentPeriod();

        return $period?->getSalary();
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
     * Récupère la période d'emploi la plus récente (active ou passée).
     * Les périodes sont déjà triées par startDate DESC grâce à l'OrderBy.
     */
    public function getMostRecentEmploymentPeriod(): ?EmploymentPeriod
    {
        return $this->employmentPeriods->first() ?: null;
    }

    /**
     * Récupère la période d'emploi pertinente : active en priorité, sinon la plus récente.
     */
    public function getRelevantEmploymentPeriod(): ?EmploymentPeriod
    {
        return $this->getCurrentEmploymentPeriod() ?? $this->getMostRecentEmploymentPeriod();
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

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }
}

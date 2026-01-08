<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\ContributorProgressRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContributorProgressRepository::class)]
#[ORM\Table(name: 'contributor_progress')]
#[ORM\Index(name: 'idx_contributorprogress_company', columns: ['company_id'])]
class ContributorProgress implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\OneToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private Contributor $contributor;

    #[ORM\Column]
    private int $totalXp = 0;

    #[ORM\Column]
    private int $level = 1;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $title = null;

    #[ORM\Column]
    private int $currentLevelXp = 0;

    #[ORM\Column]
    private int $nextLevelXp = 100;

    #[ORM\Column]
    private DateTimeImmutable $lastXpGainedAt;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->lastXpGainedAt = new DateTimeImmutable();
        $this->createdAt      = new DateTimeImmutable();
        $this->updatedAt      = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContributor(): Contributor
    {
        return $this->contributor;
    }

    public function setContributor(Contributor $contributor): static
    {
        $this->contributor = $contributor;

        return $this;
    }

    public function getTotalXp(): int
    {
        return $this->totalXp;
    }

    public function setTotalXp(int $totalXp): static
    {
        $this->totalXp = $totalXp;

        return $this;
    }

    public function addXp(int $xp): static
    {
        $this->totalXp        += $xp;
        $this->currentLevelXp += $xp;
        $this->lastXpGainedAt = new DateTimeImmutable();
        $this->updatedAt      = new DateTimeImmutable();

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getCurrentLevelXp(): int
    {
        return $this->currentLevelXp;
    }

    public function setCurrentLevelXp(int $currentLevelXp): static
    {
        $this->currentLevelXp = $currentLevelXp;

        return $this;
    }

    public function getNextLevelXp(): int
    {
        return $this->nextLevelXp;
    }

    public function setNextLevelXp(int $nextLevelXp): static
    {
        $this->nextLevelXp = $nextLevelXp;

        return $this;
    }

    public function getLastXpGainedAt(): DateTimeImmutable
    {
        return $this->lastXpGainedAt;
    }

    public function setLastXpGainedAt(DateTimeImmutable $lastXpGainedAt): static
    {
        $this->lastXpGainedAt = $lastXpGainedAt;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getProgressPercentage(): int
    {
        if ($this->nextLevelXp === 0) {
            return 100;
        }

        return (int) (($this->currentLevelXp / $this->nextLevelXp) * 100);
    }

    public function levelUp(): bool
    {
        if ($this->currentLevelXp >= $this->nextLevelXp) {
            ++$this->level;
            $this->currentLevelXp -= $this->nextLevelXp;
            $this->nextLevelXp = $this->calculateNextLevelXp();
            $this->updatedAt   = new DateTimeImmutable();

            return true;
        }

        return false;
    }

    private function calculateNextLevelXp(): int
    {
        // Formule: 100 * level^1.5
        return (int) (100 * $this->level ** 1.5);
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
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

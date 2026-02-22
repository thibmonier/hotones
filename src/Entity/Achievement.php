<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\AchievementRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AchievementRepository::class)]
#[ORM\Table(name: 'achievements')]
#[ORM\Index(name: 'idx_achievement_company', columns: ['company_id'])]
#[ORM\UniqueConstraint(name: 'unique_contributor_badge', columns: ['contributor_id', 'badge_id'])]
class Achievement implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Contributor::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Contributor $contributor;

    #[ORM\ManyToOne(targetEntity: Badge::class, inversedBy: 'achievements')]
    #[ORM\JoinColumn(nullable: false)]
    private Badge $badge;

    #[ORM\Column]
    private DateTimeImmutable $unlockedAt;

    #[ORM\Column]
    private bool $notified = false;

    public function __construct()
    {
        $this->unlockedAt = new DateTimeImmutable();
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

    public function getBadge(): Badge
    {
        return $this->badge;
    }

    public function setBadge(?Badge $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    public function getUnlockedAt(): DateTimeImmutable
    {
        return $this->unlockedAt;
    }

    public function setUnlockedAt(DateTimeImmutable $unlockedAt): static
    {
        $this->unlockedAt = $unlockedAt;

        return $this;
    }

    public function isNotified(): bool
    {
        return $this->notified;
    }

    public function setNotified(bool $notified): static
    {
        $this->notified = $notified;

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

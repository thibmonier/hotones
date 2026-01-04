<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\XpHistoryRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: XpHistoryRepository::class)]
#[ORM\Table(name: 'xp_history')]
#[ORM\Index(name: 'idx_contributor_gained', columns: ['contributor_id', 'gained_at'])]
#[ORM\Index(name: 'idx_xphistory_company', columns: ['company_id'])]
class XpHistory implements CompanyOwnedInterface
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

    #[ORM\Column]
    private int $xpAmount;

    #[ORM\Column(length: 100)]
    private string $source;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private DateTimeImmutable $gainedAt;

    public function __construct()
    {
        $this->gainedAt = new DateTimeImmutable();
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

    public function getXpAmount(): int
    {
        return $this->xpAmount;
    }

    public function setXpAmount(int $xpAmount): static
    {
        $this->xpAmount = $xpAmount;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getGainedAt(): DateTimeImmutable
    {
        return $this->gainedAt;
    }

    public function setGainedAt(DateTimeImmutable $gainedAt): static
    {
        $this->gainedAt = $gainedAt;

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

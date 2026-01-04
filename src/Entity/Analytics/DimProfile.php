<?php

declare(strict_types=1);

namespace App\Entity\Analytics;

use App\Entity\Company;
use App\Entity\Interface\CompanyOwnedInterface;
use App\Entity\Profile;
use Doctrine\ORM\Mapping as ORM;

/**
 * Table de dimension pour les profils métier
 * Permet l'analyse par profil (dev, lead dev, chef de projet, etc.).
 */
#[ORM\Entity]
#[ORM\Table(name: 'dim_profile')]
class DimProfile implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Profile $profile = null;

    #[ORM\Column(name: 'name_value', type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'boolean')]
    private bool $isProductive = true; // Indique si le profil est considéré comme productif

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    // Clé composite pour éviter les doublons
    #[ORM\Column(type: 'string', length: 150, unique: true)]
    private string $compositeKey;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): self
    {
        $this->profile = $profile;
        if ($profile && (!isset($this->name) || $this->name === '')) {
            $this->name = $profile->getName();
        }
        $this->updateCompositeKey();

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->updateCompositeKey();

        return $this;
    }

    public function getIsProductive(): bool
    {
        return $this->isProductive;
    }

    public function setIsProductive(bool $isProductive): self
    {
        $this->isProductive = $isProductive;
        $this->updateCompositeKey();

        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        $this->updateCompositeKey();

        return $this;
    }

    public function getCompositeKey(): string
    {
        return $this->compositeKey;
    }

    private function updateCompositeKey(): void
    {
        $profileId          = $this->profile ? $this->profile->getId() : 'null';
        $this->compositeKey = sprintf(
            '%s_%s_%s_%s',
            $profileId,
            md5($this->name ?? 'unknown'),
            $this->isProductive ? 'productive' : 'non_productive',
            $this->isActive ? 'active' : 'inactive',
        );
    }

    public function getDisplayName(): string
    {
        $parts   = [];
        $parts[] = $this->name;
        if ($this->isProductive) {
            $parts[] = '(Productif)';
        }

        return implode(' ', $parts);
    }

    public function isProductive(): ?bool
    {
        return $this->isProductive;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setCompositeKey(string $compositeKey): static
    {
        $this->compositeKey = $compositeKey;

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

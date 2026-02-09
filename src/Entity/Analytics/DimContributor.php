<?php

declare(strict_types=1);

namespace App\Entity\Analytics;

use App\Entity\Company;
use App\Entity\Interface\CompanyOwnedInterface;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * Table de dimension pour les contributeurs
 * Permet l'analyse par chef de projet, commercial, directeur de projet, etc.
 */
#[ORM\Entity]
#[ORM\Table(name: 'dim_contributor')]
class DimContributor implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(name: 'name_value', type: 'string', length: 180)]
    private string $name;

    #[ORM\Column(name: 'role_value', type: 'string', length: 50)]
    private string $role; // key_account_manager, project_manager, project_director, sales_person

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    // Clé composite pour éviter les doublons
    #[ORM\Column(type: 'string', length: 250, unique: true)]
    private string $compositeKey;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        if ($user && (!isset($this->name) || $this->name === '')) {
            $this->name = $user->getFullName();
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

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
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
        $userId             = $this->user ? $this->user->getId() : 'null';
        $name               = $this->name ?? 'unknown';
        $this->compositeKey = sprintf(
            '%s_%s_%s_%s',
            $userId,
            $this->role ?? 'null',
            $this->isActive ? 'active' : 'inactive',
            md5($name), // Ajout d'un hash du nom pour l'unicité
        );
    }

    public function getRoleDisplayName(): string
    {
        return match ($this->role) {
            'key_account_manager' => 'Key Account Manager',
            'project_manager'     => 'Chef de Projet',
            'project_director'    => 'Directeur de Projet',
            'sales_person'        => 'Commercial',
            default               => ucfirst(str_replace('_', ' ', $this->role)),
        };
    }

    public function getDisplayName(): string
    {
        return $this->name.' ('.$this->getRoleDisplayName().')';
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

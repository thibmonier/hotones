<?php

declare(strict_types=1);

namespace App\Entity\Analytics;

use Doctrine\ORM\Mapping as ORM;

/**
 * Table de dimension pour les types de projets
 * Permet l'analyse par type (forfait/régie), catégorie de service, statut.
 */
#[ORM\Entity]
#[ORM\Table(name: 'dim_project_type')]
class DimProjectType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $projectType; // forfait, regie

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $serviceCategory = null; // Brand, E-commerce, etc.

    #[ORM\Column(name: 'status_value', type: 'string', length: 20)]
    private string $status; // active, completed, cancelled

    #[ORM\Column(type: 'boolean')]
    private bool $isInternal = false;

    // Clé composite pour l'unicité
    #[ORM\Column(type: 'string', length: 150, unique: true)]
    private string $compositeKey;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectType(): string
    {
        return $this->projectType;
    }

    public function setProjectType(string $projectType): self
    {
        $this->projectType = $projectType;
        $this->updateCompositeKey();

        return $this;
    }

    public function getServiceCategory(): ?string
    {
        return $this->serviceCategory;
    }

    public function setServiceCategory(?string $serviceCategory): self
    {
        $this->serviceCategory = $serviceCategory;
        $this->updateCompositeKey();

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->updateCompositeKey();

        return $this;
    }

    public function getIsInternal(): bool
    {
        return $this->isInternal;
    }

    public function setIsInternal(bool $isInternal): self
    {
        $this->isInternal = $isInternal;
        $this->updateCompositeKey();

        return $this;
    }

    public function getCompositeKey(): string
    {
        return $this->compositeKey;
    }

    private function updateCompositeKey(): void
    {
        $this->compositeKey = sprintf(
            '%s_%s_%s_%s',
            $this->projectType     ?? 'null',
            $this->serviceCategory ?? 'null',
            $this->status          ?? 'null',
            $this->isInternal ? 'internal' : 'external',
        );
    }

    public function getDisplayName(): string
    {
        $parts   = [];
        $parts[] = ucfirst($this->projectType);
        if ($this->serviceCategory) {
            $parts[] = $this->serviceCategory;
        }
        $parts[] = ucfirst($this->status);
        if ($this->isInternal) {
            $parts[] = 'Interne';
        }

        return implode(' - ', $parts);
    }

    public function isInternal(): ?bool
    {
        return $this->isInternal;
    }

    public function setCompositeKey(string $compositeKey): static
    {
        $this->compositeKey = $compositeKey;

        return $this;
    }
}

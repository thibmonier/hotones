<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\BusinessUnitRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BusinessUnit entity - Hierarchical sub-organization within a Company.
 *
 * BusinessUnits allow companies to organize their operations hierarchically
 * (e.g., Direction → BU Web → Team Mobile) for better reporting and access control.
 *
 * Key Features:
 * - Self-referencing hierarchy (parent → children)
 * - Optional manager assignment
 * - Business objectives (revenue target, margin target, headcount)
 * - Cost center for analytical accounting
 * - Can be activated/deactivated
 *
 * Use Cases:
 * - Departmental organization (Sales, Engineering, Marketing)
 * - Geographic divisions (EMEA, APAC, Americas)
 * - Practice areas (Web, Mobile, Data)
 * - Project teams within departments
 */
#[ORM\Entity(repositoryClass: BusinessUnitRepository::class)]
#[ORM\Table(name: 'business_units')]
#[ORM\Index(name: 'idx_bu_company', columns: ['company_id'])]
#[ORM\Index(name: 'idx_bu_parent', columns: ['parent_id'])]
#[ORM\Index(name: 'idx_bu_active', columns: ['active'])]
#[ORM\HasLifecycleCallbacks]
class BusinessUnit implements CompanyOwnedInterface, Stringable
{
    // ===========================
    // Primary Key
    // ===========================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // ===========================
    // Company Relationship (MANDATORY)
    // ===========================

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'businessUnits')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    // ===========================
    // Hierarchical Structure (Self-Referencing)
    // ===========================

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    /** @var Collection<int, BusinessUnit> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist', 'remove'])]
    private Collection $children;

    // ===========================
    // Core Identity
    // ===========================

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // ===========================
    // Management
    // ===========================

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $manager = null;

    // ===========================
    // Business Objectives
    // ===========================

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    #[Assert\Positive]
    private ?string $annualRevenueTarget = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?string $annualMarginTarget = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    private ?int $headcountTarget = null;

    // ===========================
    // Accounting Integration
    // ===========================

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $costCenter = null;

    // ===========================
    // Status
    // ===========================

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    // ===========================
    // Timestamps
    // ===========================

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    // ===========================
    // Constructor
    // ===========================

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    // ===========================
    // Lifecycle Callbacks
    // ===========================

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    // ===========================
    // Business Logic Methods
    // ===========================

    /**
     * Get full hierarchical path (e.g., "Direction / BU Web / Team Mobile").
     *
     * @param string $separator Separator between hierarchy levels
     *
     * @return string Full path from root to this BU
     */
    public function getFullPath(string $separator = ' / '): string
    {
        $path    = [$this->name];
        $current = $this->parent;

        while ($current !== null) {
            array_unshift($path, $current->getName());
            $current = $current->getParent();
        }

        return implode($separator, $path);
    }

    /**
     * Get depth level in hierarchy (0 = root, 1 = child of root, etc.).
     *
     * @return int Depth level
     */
    public function getDepth(): int
    {
        $depth   = 0;
        $current = $this->parent;

        while ($current !== null) {
            ++$depth;
            $current = $current->getParent();
        }

        return $depth;
    }

    /**
     * Get all ancestor BUs (parent, grandparent, etc.) from nearest to root.
     *
     * @return array<int, BusinessUnit> Array of ancestor BUs
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $current   = $this->parent;

        while ($current !== null) {
            $ancestors[] = $current;
            $current     = $current->getParent();
        }

        return $ancestors;
    }

    /**
     * Get all descendant BUs (children, grandchildren, etc.) recursively.
     *
     * @return array<int, BusinessUnit> Array of all descendant BUs
     */
    public function getDescendants(): array
    {
        $descendants = [];

        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants   = array_merge($descendants, $child->getDescendants());
        }

        return $descendants;
    }

    /**
     * Check if this BU is a root BU (no parent).
     *
     * @return bool True if root, false otherwise
     */
    public function isRoot(): bool
    {
        return $this->parent === null;
    }

    /**
     * Check if this BU is a leaf BU (no children).
     *
     * @return bool True if leaf, false otherwise
     */
    public function isLeaf(): bool
    {
        return $this->children->isEmpty();
    }

    /**
     * Check if this BU is an ancestor of given BU.
     *
     * @param BusinessUnit $bu BU to check
     *
     * @return bool True if ancestor, false otherwise
     */
    public function isAncestorOf(BusinessUnit $bu): bool
    {
        $current = $bu->getParent();

        while ($current !== null) {
            if ($current->getId() === $this->id) {
                return true;
            }
            $current = $current->getParent();
        }

        return false;
    }

    /**
     * Check if this BU is a descendant of given BU.
     *
     * @param BusinessUnit $bu BU to check
     *
     * @return bool True if descendant, false otherwise
     */
    public function isDescendantOf(BusinessUnit $bu): bool
    {
        return $bu->isAncestorOf($this);
    }

    // ===========================
    // Getters & Setters
    // ===========================

    public function getId(): ?int
    {
        return $this->id;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, BusinessUnit>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(BusinessUnit $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(BusinessUnit $child): self
    {
        if ($this->children->removeElement($child)) {
            // Set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
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

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function setManager(?User $manager): self
    {
        $this->manager = $manager;

        return $this;
    }

    public function getAnnualRevenueTarget(): ?string
    {
        return $this->annualRevenueTarget;
    }

    public function setAnnualRevenueTarget(?string $annualRevenueTarget): self
    {
        $this->annualRevenueTarget = $annualRevenueTarget;

        return $this;
    }

    public function getAnnualMarginTarget(): ?string
    {
        return $this->annualMarginTarget;
    }

    public function setAnnualMarginTarget(?string $annualMarginTarget): self
    {
        $this->annualMarginTarget = $annualMarginTarget;

        return $this;
    }

    public function getHeadcountTarget(): ?int
    {
        return $this->headcountTarget;
    }

    public function setHeadcountTarget(?int $headcountTarget): self
    {
        $this->headcountTarget = $headcountTarget;

        return $this;
    }

    public function getCostCenter(): ?string
    {
        return $this->costCenter;
    }

    public function setCostCenter(?string $costCenter): self
    {
        $this->costCenter = $costCenter;

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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // ===========================
    // String Representation
    // ===========================

    public function __toString(): string
    {
        return $this->getFullPath();
    }
}

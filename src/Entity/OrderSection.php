<?php

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'order_sections', indexes: [
    new ORM\Index(name: 'idx_order_section_order', columns: ['order_id']),
    new ORM\Index(name: 'idx_ordersection_company', columns: ['company_id']),
])]
class OrderSection implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'sections')]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private int $position = 0; // Pour ordonner les sections

    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'section', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(OrderLine $line): self
    {
        if (!$this->lines->contains($line)) {
            $this->lines[] = $line;
            $line->setSection($this);
        }

        return $this;
    }

    public function removeLine(OrderLine $line): self
    {
        if ($this->lines->removeElement($line)) {
            if ($line->getSection() === $this) {
                $line->setSection(null);
            }
        }

        return $this;
    }

    /**
     * Calcule le total de la section (somme des lignes).
     */
    public function getTotalAmount(): string
    {
        $total = '0';
        foreach ($this->lines as $line) {
            $total = bcadd($total, (string) $line->getTotalAmount(), 2);
        }

        return $total;
    }

    /**
     * Calcule le total des jours vendus dans cette section.
     */
    public function getTotalDays(): string
    {
        $total = '0';
        foreach ($this->lines as $line) {
            if ($line->getProfile()) { // Seulement les lignes avec profil
                $total = bcadd($total, (string) $line->getDays(), 2);
            }
        }

        return $total;
    }

    // Méthodes alias pour compatibilité
    public function getName(): string
    {
        return $this->getTitle();
    }

    public function setName(string $name): self
    {
        return $this->setTitle($name);
    }

    public function getSortOrder(): int
    {
        return $this->getPosition();
    }

    public function setSortOrder(int $sortOrder): self
    {
        return $this->setPosition($sortOrder);
    }

    /**
     * Calcule la marge brute totale de la section.
     */
    public function getTotalGrossMargin(): string
    {
        $total = '0';
        foreach ($this->lines as $line) {
            $total = bcadd($total, (string) $line->getGrossMargin(), 2);
        }

        return $total;
    }

    /**
     * Calcule le coût estimé total de la section.
     */
    public function getTotalEstimatedCost(): string
    {
        $total = '0';
        foreach ($this->lines as $line) {
            $total = bcadd($total, (string) $line->getEstimatedCost(), 2);
        }

        return $total;
    }

    /**
     * Calcule le taux de marge moyen de la section.
     */
    public function getMarginRate(): string
    {
        $totalRevenue = '0';
        foreach ($this->lines as $line) {
            $totalRevenue = bcadd($totalRevenue, (string) $line->getServiceAmount(), 2);
        }

        if (bccomp($totalRevenue, '0', 2) <= 0) {
            return '0';
        }

        $totalMargin = $this->getTotalGrossMargin();

        return bcmul(bcdiv($totalMargin, $totalRevenue, 4), '100', 2);
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

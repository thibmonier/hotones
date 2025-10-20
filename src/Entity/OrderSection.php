<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'order_sections')]
class OrderSection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

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
     * Calcule le total de la section (somme des lignes)
     */
    public function getTotalAmount(): string
    {
        $total = '0';
        foreach ($this->lines as $line) {
            $total = bcadd($total, $line->getTotalAmount(), 2);
        }
        return $total;
    }

    /**
     * Calcule le total des jours vendus dans cette section
     */
    public function getTotalDays(): string
    {
        $total = '0';
        foreach ($this->lines as $line) {
            if ($line->getProfile()) { // Seulement les lignes avec profil
                $total = bcadd($total, $line->getDays(), 2);
            }
        }
        return $total;
    }
}

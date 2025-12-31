<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Interface\CompanyOwnedInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ligne de facturation (InvoiceLine).
 */
#[ORM\Entity]
#[ORM\Table(name: 'invoice_lines')]
#[ORM\Index(name: 'idx_invoice_line_company', columns: ['company_id'])]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['invoice:read']],
    denormalizationContext: ['groups' => ['invoice:write']],
)]
class InvoiceLine implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['invoice:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    /**
     * Facture parente.
     */
    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Invoice $invoice = null;

    /**
     * Description de la ligne.
     */
    #[ORM\Column(type: 'text')]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $description;

    /**
     * Quantité (jours, heures, unités, etc.).
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $quantity = '1.00';

    /**
     * Unité (jour, heure, forfait, etc.).
     */
    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $unit = 'jour';

    /**
     * Prix unitaire HT.
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $unitPriceHt = '0.00';

    /**
     * Montant total HT (quantity * unitPriceHt).
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $totalHt = '0.00';

    /**
     * Taux de TVA appliqué (%).
     */
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $tvaRate = '20.00';

    /**
     * Montant TVA.
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $tvaAmount = '0.00';

    /**
     * Montant total TTC.
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['invoice:read', 'invoice:write'])]
    private string $totalTtc = '0.00';

    /**
     * Ordre d'affichage.
     */
    #[ORM\Column(type: 'integer')]
    #[Groups(['invoice:read', 'invoice:write'])]
    private int $displayOrder = 0;

    /**
     * Calcule les montants totaux à partir de la quantité et du prix unitaire.
     */
    public function calculateAmounts(): self
    {
        // Total HT = quantité × prix unitaire
        $this->totalHt = bcmul($this->quantity, $this->unitPriceHt, 2);

        // TVA = total HT × taux TVA
        $this->tvaAmount = bcmul($this->totalHt, bcdiv($this->tvaRate, '100', 4), 2);

        // Total TTC = total HT + TVA
        $this->totalTtc = bcadd($this->totalHt, $this->tvaAmount, 2);

        return $this;
    }

    // Getters & Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getUnitPriceHt(): string
    {
        return $this->unitPriceHt;
    }

    public function setUnitPriceHt(string $unitPriceHt): self
    {
        $this->unitPriceHt = $unitPriceHt;

        return $this;
    }

    public function getTotalHt(): string
    {
        return $this->totalHt;
    }

    public function setTotalHt(string $totalHt): self
    {
        $this->totalHt = $totalHt;

        return $this;
    }

    public function getTvaRate(): string
    {
        return $this->tvaRate;
    }

    public function setTvaRate(string $tvaRate): self
    {
        $this->tvaRate = $tvaRate;

        return $this;
    }

    public function getTvaAmount(): string
    {
        return $this->tvaAmount;
    }

    public function setTvaAmount(string $tvaAmount): self
    {
        $this->tvaAmount = $tvaAmount;

        return $this;
    }

    public function getTotalTtc(): string
    {
        return $this->totalTtc;
    }

    public function setTotalTtc(string $totalTtc): self
    {
        $this->totalTtc = $totalTtc;

        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;

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

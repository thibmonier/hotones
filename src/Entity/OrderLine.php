<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_lines')]
class OrderLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OrderSection::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false)]
    private OrderSection $section;

    #[ORM\Column(type: 'string', length: 255)]
    private string $description;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    // Profil pour les lignes de service (peut être null pour les achats)
    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Profile $profile = null;

    // TJM de vente pour cette ligne (si profil)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $dailyRate = null;

    // Nombre de jours vendus (si profil)
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $days = null;

    // Montant direct (pour les achats ou montants fixes)
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $directAmount = null;

    // Achat attaché à cette ligne
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $attachedPurchaseAmount = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type = 'service'; // service, purchase, fixed_amount

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSection(): OrderSection
    {
        return $this->section;
    }
    public function setSection(OrderSection $section): self
    {
        $this->section = $section;
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

    public function getPosition(): int
    {
        return $this->position;
    }
    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }
    public function setProfile(?Profile $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    public function getDailyRate(): ?string
    {
        return $this->dailyRate;
    }
    public function setDailyRate(?string $dailyRate): self
    {
        $this->dailyRate = $dailyRate;
        return $this;
    }

    public function getDays(): ?string
    {
        return $this->days;
    }
    public function setDays(?string $days): self
    {
        $this->days = $days;
        return $this;
    }

    public function getDirectAmount(): ?string
    {
        return $this->directAmount;
    }
    public function setDirectAmount(?string $directAmount): self
    {
        $this->directAmount = $directAmount;
        return $this;
    }

    public function getAttachedPurchaseAmount(): ?string
    {
        return $this->attachedPurchaseAmount;
    }
    public function setAttachedPurchaseAmount(?string $attachedPurchaseAmount): self
    {
        $this->attachedPurchaseAmount = $attachedPurchaseAmount;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }
    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * Calcule le montant total de cette ligne
     */
    public function getTotalAmount(): string
    {
        switch ($this->type) {
            case 'service':
                if ($this->profile && $this->dailyRate && $this->days) {
                    $serviceAmount = bcmul($this->dailyRate, $this->days, 2);
                    $purchaseAmount = $this->attachedPurchaseAmount ?? '0';
                    return bcadd($serviceAmount, $purchaseAmount, 2);
                }
                return '0';

            case 'purchase':
            case 'fixed_amount':
                return $this->directAmount ?? '0';

            default:
                return '0';
        }
    }

    /**
     * Calcule le montant de service uniquement (sans achat attaché)
     */
    public function getServiceAmount(): string
    {
        if ($this->type === 'service' && $this->profile && $this->dailyRate && $this->days) {
            return bcmul($this->dailyRate, $this->days, 2);
        }
        return '0';
    }

    /**
     * Vérifie si cette ligne compte dans les calculs de rentabilité
     */
    public function isCountableForProfitability(): bool
    {
        // Les achats directs ne comptent pas dans la rentabilité
        return $this->type !== 'purchase';
    }

    // Méthodes alias pour compatibilité avec le contrôleur
    public function getTjm(): ?string
    {
        return $this->getDailyRate();
    }

    public function setTjm(?string $tjm): self
    {
        return $this->setDailyRate($tjm);
    }

    public function getPurchaseAmount(): ?string
    {
        return $this->getAttachedPurchaseAmount();
    }

    public function setPurchaseAmount(?string $purchaseAmount): self
    {
        return $this->setAttachedPurchaseAmount($purchaseAmount);
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
     * Calcule la marge brute de cette ligne (CA - coût estimé)
     */
    public function getGrossMargin(): string
    {
        if ($this->type !== 'service' || !$this->profile || !$this->days) {
            return '0';
        }

        $revenue = $this->getServiceAmount(); // CA sans achats
        $cost = $this->getEstimatedCost();
        
        return bcsub($revenue, $cost, 2);
    }

    /**
     * Calcule le coût estimé de cette ligne (jours * CJM du profil)
     */
    public function getEstimatedCost(): string
    {
        if ($this->type !== 'service' || !$this->profile || !$this->days) {
            return '0';
        }

        // Utiliser le TJM par défaut du profil comme base de coût
        // TODO: Améliorer en utilisant les périodes d'emploi des contributeurs
        $defaultRate = $this->profile->getDefaultDailyRate();
        if (!$defaultRate) {
            return '0';
        }

        // Estimation : coût = 70% du TJM par défaut (marge standard)
        $estimatedCostRate = bcmul($defaultRate, '0.7', 2);
        return bcmul($this->days, $estimatedCostRate, 2);
    }

    /**
     * Calcule le taux de marge de cette ligne
     */
    public function getMarginRate(): string
    {
        $revenue = $this->getServiceAmount();
        if (bccomp($revenue, '0', 2) <= 0) {
            return '0';
        }

        $margin = $this->getGrossMargin();
        return bcmul(bcdiv($margin, $revenue, 4), '100', 2);
    }
}

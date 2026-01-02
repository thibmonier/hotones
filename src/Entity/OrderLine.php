<?php

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'order_lines', indexes: [
    new ORM\Index(name: 'idx_order_line_section', columns: ['section_id']),
    new ORM\Index(name: 'idx_order_line_profile', columns: ['profile_id']),
    new ORM\Index(name: 'idx_order_line_type', columns: ['type']),
    new ORM\Index(name: 'idx_orderline_company', columns: ['company_id']),
])]
class OrderLine implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    public Company $company {
        get => $this->company;
        set {
            $this->company = $value;
        }
    }

    #[ORM\ManyToOne(targetEntity: OrderSection::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false)]
    public OrderSection $section {
        get => $this->section;
        set {
            $this->section = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 255)]
    public string $description {
        get => $this->description;
        set {
            $this->description = $value;
        }
    }

    #[ORM\Column(type: 'integer')]
    public int $position = 0 {
        get => $this->position;
        set {
            $this->position = $value;
        }
    }

    // Profil pour les lignes de service (peut être null pour les achats)
    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?Profile $profile = null {
        get => $this->profile;
        set {
            $this->profile = $value;
        }
    }

    // TJM de vente pour cette ligne (si profil)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    public ?string $dailyRate = null {
        get => $this->dailyRate;
        set {
            $this->dailyRate = $value;
        }
    }

    // Nombre de jours vendus (si profil)
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    public ?string $days = null {
        get => $this->days;
        set {
            $this->days = $value;
        }
    }

    // Montant direct (pour les achats ou montants fixes)
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    public ?string $directAmount = null {
        get => $this->directAmount;
        set {
            $this->directAmount = $value;
        }
    }

    // Achat attaché à cette ligne
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    public ?string $attachedPurchaseAmount = null {
        get => $this->attachedPurchaseAmount;
        set {
            $this->attachedPurchaseAmount = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 50)]
    public string $type = 'service' { // service, purchase, fixed_amount
        get => $this->type;
        set {
            $this->type = $value;
        }
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $notes = null {
        get => $this->notes;
        set {
            $this->notes = $value;
        }
    }

    /**
     * Calcule le montant total de cette ligne.
     */
    public function getTotalAmount(): string
    {
        switch ($this->type) {
            case 'service':
                if ($this->profile && $this->dailyRate && $this->days) {
                    $serviceAmount  = bcmul($this->dailyRate, $this->days, 2);
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
     * Calcule le montant de service uniquement (sans achat attaché).
     */
    public function getServiceAmount(): string
    {
        if ($this->type === 'service' && $this->profile && $this->dailyRate && $this->days) {
            return bcmul($this->dailyRate, $this->days, 2);
        }

        return '0';
    }

    /**
     * Vérifie si cette ligne compte dans les calculs de rentabilité.
     */
    public function isCountableForProfitability(): bool
    {
        // Les achats directs ne comptent pas dans la rentabilité
        return $this->type !== 'purchase';
    }

    // Méthodes alias pour compatibilité avec le contrôleur
    public function getTjm(): ?string
    {
        return $this->dailyRate;
    }

    public function setTjm(?string $tjm): self
    {
        $this->dailyRate = $tjm;

        return $this;
    }

    public function getPurchaseAmount(): ?string
    {
        return $this->attachedPurchaseAmount;
    }

    public function setPurchaseAmount(?string $purchaseAmount): self
    {
        $this->attachedPurchaseAmount = $purchaseAmount;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->position;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->position = $sortOrder;

        return $this;
    }

    /**
     * Calcule la marge brute de cette ligne (CA - coût estimé).
     */
    public function getGrossMargin(): string
    {
        if ($this->type !== 'service' || !$this->profile || !$this->days) {
            return '0';
        }

        $revenue = $this->getServiceAmount(); // CA sans achats
        $cost    = $this->getEstimatedCost();

        return bcsub($revenue, $cost, 2);
    }

    /**
     * Calcule le coût estimé de cette ligne (jours * CJM du profil).
     *
     * Priorité 1 : Utilise le CJM (Coût Journalier Moyen) du profil si disponible,
     *              ajusté par le coefficient de marge.
     * Priorité 2 : Fallback sur l'ancienne méthode (70% du TJM par défaut).
     */
    public function getEstimatedCost(): string
    {
        if ($this->type !== 'service' || !$this->profile || !$this->days) {
            return '0';
        }

        // Priorité 1: Utiliser le CJM du profil si disponible
        $cjm = $this->profile->getCjm();
        if ($cjm !== null) {
            // Appliquer le coefficient de marge (par défaut 1.0)
            $marginCoefficient = $this->profile->getMarginCoefficient() ?? '1.00';
            $adjustedCjm       = bcmul($cjm, $marginCoefficient, 2);

            return bcmul($this->days, $adjustedCjm, 2);
        }

        // Fallback : utiliser l'ancienne méthode (70% du TJM par défaut)
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
     * Calcule le taux de marge de cette ligne.
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

    /**
     * Crée une ProjectTask depuis cette ligne budgétaire.
     * Utile lors de la validation d'un devis pour générer automatiquement les tâches du projet.
     *
     * @param Project $project Le projet auquel attacher la tâche
     *
     * @return ProjectTask|null La tâche créée, ou null si la ligne n'est pas de type service
     */
    public function createProjectTask(Project $project): ?ProjectTask
    {
        // Seules les lignes de type service génèrent des tâches
        if ($this->type !== 'service' || !$this->profile || !$this->days) {
            return null;
        }

        $task = new ProjectTask();
        $task->setProject($project);
        $task->setOrderLine($this);

        // Nom de la tâche = description de la ligne
        $task->setName($this->description);

        // Type et propriétés par défaut
        $task->setType(ProjectTask::TYPE_REGULAR);
        $task->setCountsForProfitability(true);
        $task->setActive(true);

        // Heures vendues = jours × 8
        $soldHours = (int) round(bcmul($this->days, '8', 2));
        $task->setEstimatedHoursSold($soldHours);

        // Profil requis
        $task->setRequiredProfile($this->profile);

        // TJM de la tâche = TJM de la ligne
        $task->setDailyRate($this->dailyRate);

        // Notes
        if ($this->notes) {
            $task->setDescription($this->notes);
        }

        return $task;
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

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->section.
     */
    public function getSection(): OrderSection
    {
        return $this->section;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->section = $value.
     */
    public function setSection(OrderSection $value): self
    {
        $this->section = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->description = $value.
     */
    public function setDescription(string $value): self
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->position.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->position = $value.
     */
    public function setPosition(int $value): self
    {
        $this->position = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->profile.
     */
    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->profile = $value.
     */
    public function setProfile(?Profile $value): self
    {
        $this->profile = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->dailyRate.
     */
    public function getDailyRate(): ?string
    {
        return $this->dailyRate;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->dailyRate = $value.
     */
    public function setDailyRate(?string $value): self
    {
        $this->dailyRate = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->days.
     */
    public function getDays(): ?string
    {
        return $this->days;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->days = $value.
     */
    public function setDays(?string $value): self
    {
        $this->days = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->directAmount.
     */
    public function getDirectAmount(): ?string
    {
        return $this->directAmount;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->directAmount = $value.
     */
    public function setDirectAmount(?string $value): self
    {
        $this->directAmount = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->attachedPurchaseAmount.
     */
    public function getAttachedPurchaseAmount(): ?string
    {
        return $this->attachedPurchaseAmount;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->attachedPurchaseAmount = $value.
     */
    public function setAttachedPurchaseAmount(?string $value): self
    {
        $this->attachedPurchaseAmount = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->type = $value.
     */
    public function setType(string $value): self
    {
        $this->type = $value;

        return $this;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->notes.
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Compatibility method for existing code.
     * With PHP 8.4 property hooks, prefer direct access: $orderLine->notes = $value.
     */
    public function setNotes(?string $value): self
    {
        $this->notes = $value;

        return $this;
    }
}

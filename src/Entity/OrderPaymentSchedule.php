<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_payment_schedules')]
class OrderPaymentSchedule
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED   = 'fixed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'paymentSchedules')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(type: 'date')]
    private DateTimeInterface $billingDate;

    #[ORM\Column(type: 'string', length: 20)]
    private string $amountType = self::TYPE_PERCENT; // percent|fixed

    // Si percent: 0-100
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $percent = null;

    // Si fixed: montant en €
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $fixedAmount = null;

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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getBillingDate(): DateTimeInterface
    {
        return $this->billingDate;
    }

    public function setBillingDate(DateTimeInterface $date): self
    {
        $this->billingDate = $date;

        return $this;
    }

    public function getAmountType(): string
    {
        return $this->amountType;
    }

    public function setAmountType(string $type): self
    {
        $this->amountType = $type;

        return $this;
    }

    public function getPercent(): ?string
    {
        return $this->percent;
    }

    public function setPercent(?string $percent): self
    {
        $this->percent = $percent;

        return $this;
    }

    public function getFixedAmount(): ?string
    {
        return $this->fixedAmount;
    }

    public function setFixedAmount(?string $amount): self
    {
        $this->fixedAmount = $amount;

        return $this;
    }

    /**
     * Montant calculé en € pour cette échéance selon le total du devis.
     */
    public function computeAmount(string $orderTotal): string
    {
        if ($this->amountType === self::TYPE_FIXED) {
            return $this->fixedAmount ?? '0.00';
        }

        $pct = $this->percent ?? '0';

        return bcmul($orderTotal, bcdiv($pct, '100', 4), 2);
    }
}

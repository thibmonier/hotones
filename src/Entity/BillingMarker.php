<?php

namespace App\Entity;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Repository\BillingMarkerRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BillingMarkerRepository::class)]
#[ORM\Table(name: 'billing_markers')]
#[ORM\UniqueConstraint(name: 'uniq_marker_schedule', columns: ['schedule_id'])]
#[ORM\UniqueConstraint(name: 'uniq_marker_regie_period', columns: ['order_id', 'year', 'month'])]
#[ORM\Index(name: 'idx_billingmarker_company', columns: ['company_id'])]
class BillingMarker implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Company $company;

    // Option 1: rattachement à une échéance (forfait)
    #[ORM\OneToOne(targetEntity: OrderPaymentSchedule::class)]
    #[ORM\JoinColumn(name: 'schedule_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?OrderPaymentSchedule $schedule = null;

    // Option 2: régie mensuelle → rattachement au devis + période
    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $year = null; // YYYY (pour régie)

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $month = null; // 1..12 (pour régie)

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isIssued = false;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $issuedAt = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $paidAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSchedule(): ?OrderPaymentSchedule
    {
        return $this->schedule;
    }

    public function setSchedule(?OrderPaymentSchedule $schedule): self
    {
        $this->schedule = $schedule;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(?int $month): self
    {
        $this->month = $month;

        return $this;
    }

    public function isIssued(): bool
    {
        return $this->isIssued;
    }

    public function setIsIssued(bool $issued): self
    {
        $this->isIssued = $issued;

        return $this;
    }

    public function getIssuedAt(): ?DateTimeInterface
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(?DateTimeInterface $issuedAt): self
    {
        $this->issuedAt = $issuedAt;

        return $this;
    }

    public function getPaidAt(): ?DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?DateTimeInterface $paidAt): self
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

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

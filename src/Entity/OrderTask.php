<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_tasks')]
class OrderTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\Column(type: 'string', length: 180)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // Profil requis pour cette tâche (développeur, lead developer, chef de projet, product owner)
    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: 'orderTasks')]
    #[ORM\JoinColumn(nullable: false)]
    private Profile $profile;

    // Nombre de jours vendus pour cette tâche
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $soldDays;

    // TJM de vente spécifique pour cette tâche/profil
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $soldDailyRate;

    // Montant HT de cette tâche (soldDays * soldDailyRate)
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $totalAmount;

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

    public function getProfile(): Profile
    {
        return $this->profile;
    }
    public function setProfile(Profile $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    public function getSoldDays(): string
    {
        return $this->soldDays;
    }
    public function setSoldDays(string $soldDays): self
    {
        $this->soldDays = $soldDays;
        $this->updateTotalAmount();
        return $this;
    }

    public function getSoldDailyRate(): string
    {
        return $this->soldDailyRate;
    }
    public function setSoldDailyRate(string $soldDailyRate): self
    {
        $this->soldDailyRate = $soldDailyRate;
        $this->updateTotalAmount();
        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    private function updateTotalAmount(): void
    {
        if (isset($this->soldDays) && isset($this->soldDailyRate)) {
            $this->totalAmount = bcmul($this->soldDays, $this->soldDailyRate, 2);
        }
    }
}

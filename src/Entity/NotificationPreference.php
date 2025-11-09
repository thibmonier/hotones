<?php

namespace App\Entity;

use App\Enum\NotificationType;
use App\Repository\NotificationPreferenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationPreferenceRepository::class)]
#[ORM\Table(name: 'notification_preferences')]
#[ORM\UniqueConstraint(name: 'user_event_unique', columns: ['user_id', 'event_type'])]
class NotificationPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, enumType: NotificationType::class)]
    private NotificationType $eventType;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $inApp = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $email = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $webhook = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEventType(): NotificationType
    {
        return $this->eventType;
    }

    public function setEventType(NotificationType $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function isInApp(): bool
    {
        return $this->inApp;
    }

    public function setInApp(bool $inApp): self
    {
        $this->inApp = $inApp;

        return $this;
    }

    public function isEmail(): bool
    {
        return $this->email;
    }

    public function setEmail(bool $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function isWebhook(): bool
    {
        return $this->webhook;
    }

    public function setWebhook(bool $webhook): self
    {
        $this->webhook = $webhook;

        return $this;
    }
}

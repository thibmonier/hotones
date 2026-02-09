<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;
use App\Enum\NotificationType;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Classe de base pour tous les événements de notification.
 */
abstract class NotificationEvent extends Event
{
    /**
     * @param User[] $recipients
     */
    public function __construct(
        private readonly NotificationType $type,
        private readonly string $title,
        private readonly string $message,
        private readonly array $recipients,
        private readonly ?array $data = null,
        private readonly ?string $entityType = null,
        private readonly ?int $entityId = null,
    ) {
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return User[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }
}

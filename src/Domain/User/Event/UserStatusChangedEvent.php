<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserStatus;
use DateTimeImmutable;

/**
 * Domain event raised when a user's status is changed.
 */
final readonly class UserStatusChangedEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        private UserId $userId,
        private UserStatus $previousStatus,
        private UserStatus $newStatus,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        $this->occurredOn = $occurredOn ?? new DateTimeImmutable();
    }

    public static function create(
        UserId $userId,
        UserStatus $previousStatus,
        UserStatus $newStatus,
    ): self {
        return new self($userId, $previousStatus, $newStatus);
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getPreviousStatus(): UserStatus
    {
        return $this->previousStatus;
    }

    public function getNewStatus(): UserStatus
    {
        return $this->newStatus;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserRole;
use DateTimeImmutable;

/**
 * Domain event raised when a user's role is changed.
 */
final readonly class UserRoleChangedEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        private UserId $userId,
        private UserRole $previousRole,
        private UserRole $newRole,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        $this->occurredOn = $occurredOn ?? new DateTimeImmutable();
    }

    public static function create(
        UserId $userId,
        UserRole $previousRole,
        UserRole $newRole,
    ): self {
        return new self($userId, $previousRole, $newRole);
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getPreviousRole(): UserRole
    {
        return $this->previousRole;
    }

    public function getNewRole(): UserRole
    {
        return $this->newRole;
    }

    public function isPromotion(): bool
    {
        return $this->newRole->isHigherThan($this->previousRole);
    }

    public function isDemotion(): bool
    {
        return $this->previousRole->isHigherThan($this->newRole);
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

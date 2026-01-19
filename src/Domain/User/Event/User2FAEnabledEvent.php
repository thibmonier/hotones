<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\User\ValueObject\UserId;
use DateTimeImmutable;

/**
 * Domain event raised when 2FA is enabled for a user.
 */
final readonly class User2FAEnabledEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct(
        private UserId $userId,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        $this->occurredOn = $occurredOn ?? new DateTimeImmutable();
    }

    public static function create(UserId $userId): self
    {
        return new self($userId);
    }

    public function getUserId(): UserId
    {
        return $this->userId;
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

<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Shared\Interface\DomainEventInterface;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserRole;

/**
 * Domain event raised when a new user is created.
 */
final readonly class UserCreatedEvent implements DomainEventInterface
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private UserId $userId,
        private CompanyId $companyId,
        private Email $email,
        private UserRole $role,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        $this->occurredOn = $occurredOn ?? new \DateTimeImmutable();
    }

    public static function create(
        UserId $userId,
        CompanyId $companyId,
        Email $email,
        UserRole $role,
    ): self {
        return new self($userId, $companyId, $email, $role);
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

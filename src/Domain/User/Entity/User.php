<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Shared\Interface\AggregateRootInterface;
use App\Domain\Shared\Trait\RecordsDomainEvents;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\User\Event\User2FAEnabledEvent;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Event\UserRoleChangedEvent;
use App\Domain\User\Event\UserStatusChangedEvent;
use App\Domain\User\Exception\InvalidUserRoleChangeException;
use App\Domain\User\Exception\InvalidUserStatusTransitionException;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserRole;
use App\Domain\User\ValueObject\UserStatus;

/**
 * User aggregate root - represents a user within a company (multi-tenant).
 */
final class User implements AggregateRootInterface
{
    use RecordsDomainEvents;

    private UserId $id;
    private CompanyId $companyId;
    private Email $email;
    private string $firstName;
    private string $lastName;
    private string $hashedPassword;
    private UserRole $role;
    private UserStatus $status;

    // 2FA configuration
    private bool $twoFactorEnabled;
    private ?string $twoFactorSecret;

    // Preferences
    private string $locale;
    private string $timezone;

    // Timestamps
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;
    private ?\DateTimeImmutable $lastLoginAt;
    private ?\DateTimeImmutable $passwordChangedAt;

    private function __construct(
        UserId $id,
        CompanyId $companyId,
        Email $email,
        string $firstName,
        string $lastName,
        string $hashedPassword,
        UserRole $role,
    ) {
        $this->id = $id;
        $this->companyId = $companyId;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->hashedPassword = $hashedPassword;
        $this->role = $role;
        $this->status = UserStatus::PENDING;

        // 2FA disabled by default
        $this->twoFactorEnabled = false;
        $this->twoFactorSecret = null;

        // Default preferences
        $this->locale = 'fr';
        $this->timezone = 'Europe/Paris';

        // Timestamps
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
        $this->lastLoginAt = null;
        $this->passwordChangedAt = new \DateTimeImmutable();
    }

    public static function create(
        UserId $id,
        CompanyId $companyId,
        Email $email,
        string $firstName,
        string $lastName,
        string $hashedPassword,
        UserRole $role = UserRole::INTERVENANT,
    ): self {
        $user = new self($id, $companyId, $email, $firstName, $lastName, $hashedPassword, $role);

        $user->recordEvent(
            UserCreatedEvent::create($id, $companyId, $email, $role)
        );

        return $user;
    }

    // Status management

    public function changeStatus(UserStatus $newStatus): void
    {
        if ($this->status === $newStatus) {
            return;
        }

        if (!$this->status->canTransitionTo($newStatus)) {
            throw InvalidUserStatusTransitionException::create($this->status, $newStatus);
        }

        $previousStatus = $this->status;
        $this->status = $newStatus;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(
            UserStatusChangedEvent::create($this->id, $previousStatus, $newStatus)
        );
    }

    public function activate(): void
    {
        $this->changeStatus(UserStatus::ACTIVE);
    }

    public function suspend(): void
    {
        $this->changeStatus(UserStatus::SUSPENDED);
    }

    public function deactivate(): void
    {
        $this->changeStatus(UserStatus::DEACTIVATED);
    }

    // Role management

    public function changeRole(UserRole $newRole): void
    {
        if ($this->role === $newRole) {
            throw InvalidUserRoleChangeException::sameRole($newRole);
        }

        $previousRole = $this->role;
        $this->role = $newRole;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(
            UserRoleChangedEvent::create($this->id, $previousRole, $newRole)
        );
    }

    public function promote(UserRole $newRole): void
    {
        if (!$this->role->canPromoteTo($newRole)) {
            throw InvalidUserRoleChangeException::sameRole($newRole);
        }

        $this->changeRole($newRole);
    }

    public function demote(UserRole $newRole): void
    {
        if (!$this->role->canDemoteTo($newRole)) {
            throw InvalidUserRoleChangeException::sameRole($newRole);
        }

        $this->changeRole($newRole);
    }

    // 2FA management

    public function enable2FA(string $secret): void
    {
        $this->twoFactorEnabled = true;
        $this->twoFactorSecret = $secret;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(User2FAEnabledEvent::create($this->id));
    }

    public function disable2FA(): void
    {
        $this->twoFactorEnabled = false;
        $this->twoFactorSecret = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Profile management

    public function updateProfile(string $firstName, string $lastName): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateEmail(Email $newEmail): void
    {
        $this->email = $newEmail;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updatePassword(string $newHashedPassword): void
    {
        $this->hashedPassword = $newHashedPassword;
        $this->passwordChangedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updatePreferences(string $locale, string $timezone): void
    {
        $this->locale = $locale;
        $this->timezone = $timezone;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function recordLogin(): void
    {
        $this->lastLoginAt = new \DateTimeImmutable();
    }

    // Calculated values

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    public function canLogin(): bool
    {
        return $this->status->canLogin();
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isSuspended(): bool
    {
        return $this->status->isSuspended();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role->hasAtLeast($role);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPERADMIN;
    }

    public function isManager(): bool
    {
        return $this->role->hasAtLeast(UserRole::MANAGER);
    }

    /**
     * @return array<string>
     */
    public function getSecurityRoles(): array
    {
        return $this->role->getInheritedRoles();
    }

    // Getters

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getCompanyId(): CompanyId
    {
        return $this->companyId;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getHashedPassword(): string
    {
        return $this->hashedPassword;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function getPasswordChangedAt(): ?\DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }
}

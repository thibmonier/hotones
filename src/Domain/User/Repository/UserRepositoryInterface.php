<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\User\Entity\User;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserStatus;

/**
 * Repository interface for User aggregate root.
 */
interface UserRepositoryInterface
{
    /**
     * Find a user by ID.
     *
     * @throws UserNotFoundException
     */
    public function findById(UserId $id): User;

    /**
     * Find a user by ID, or null if not found.
     */
    public function findByIdOrNull(UserId $id): ?User;

    /**
     * Find a user by email within a company.
     */
    public function findByEmail(Email $email): ?User;

    /**
     * Find a user by email within a specific company.
     */
    public function findByEmailAndCompany(Email $email, CompanyId $companyId): ?User;

    /**
     * Find all users for a company.
     *
     * @return User[]
     */
    public function findByCompany(CompanyId $companyId): array;

    /**
     * Find users by company and status.
     *
     * @return User[]
     */
    public function findByCompanyAndStatus(CompanyId $companyId, UserStatus $status): array;

    /**
     * Count users for a company.
     */
    public function countByCompany(CompanyId $companyId): int;

    /**
     * Check if an email is already used within a company.
     */
    public function existsByEmailInCompany(Email $email, CompanyId $companyId): bool;

    /**
     * Save a user (create or update).
     */
    public function save(User $user): void;

    /**
     * Delete a user.
     */
    public function delete(User $user): void;
}

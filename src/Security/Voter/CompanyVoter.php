<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Entity\User;
use App\Security\CompanyContext;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * CompanyVoter - Authorization voter for tenant isolation enforcement.
 *
 * This voter enforces tenant isolation by verifying that entities
 * belong to the current user's company context before allowing access.
 *
 * Supported Attributes:
 * - COMPANY_VIEW: View/read entity data
 * - COMPANY_EDIT: Modify entity data
 * - COMPANY_DELETE: Delete entity
 *
 * Supported Subjects:
 * - Any entity implementing CompanyOwnedInterface
 *
 * Security Rules:
 * 1. DENY if entity belongs to different company (cross-tenant access)
 * 2. ALLOW if ROLE_SUPERADMIN (with logging for audit trail)
 * 3. Delegate to role-based permissions for VIEW/EDIT/DELETE
 *
 * Security Note: This is a critical security boundary. All cross-tenant
 * access attempts are logged for security auditing.
 */
class CompanyVoter extends Voter
{
    public const VIEW   = 'COMPANY_VIEW';
    public const EDIT   = 'COMPANY_EDIT';
    public const DELETE = 'COMPANY_DELETE';

    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly LoggerInterface $securityLogger
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof CompanyOwnedInterface;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?\Symfony\Component\Security\Core\Authorization\Voter\Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var CompanyOwnedInterface $subject */
        $subjectCompany = $subject->getCompany();
        $currentCompany = $this->companyContext->getCurrentCompany();

        // CRITICAL: Tenant isolation check
        if ($subjectCompany->getId() !== $currentCompany->getId()) {
            // Log security violation for audit trail
            $this->logTenantViolation($user, $attribute, $subject, $subjectCompany->getId());

            // SUPERADMIN can access cross-tenant data (with logging)
            if ($user->isSuperAdmin()) {
                $this->securityLogger->warning('SUPERADMIN cross-tenant access (allowed)', [
                    'user_id'             => $user->getId(),
                    'user_company_id'     => $currentCompany->getId(),
                    'accessed_company_id' => $subjectCompany->getId(),
                    'attribute'           => $attribute,
                    'entity_class'        => get_class($subject),
                    'entity_id'           => method_exists($subject, 'getId') ? $subject->getId() : null,
                ]);

                return true;
            }

            // DENY for regular users
            return false;
        }

        // Company isolation passed, check action permissions
        return match ($attribute) {
            self::VIEW   => $this->canView($user, $subject),
            self::EDIT   => $this->canEdit($user, $subject),
            self::DELETE => $this->canDelete($user, $subject),
            default      => false,
        };
    }

    /**
     * Check if user can view entity.
     *
     * @param User                  $user   Current user
     * @param CompanyOwnedInterface $entity Entity to view
     *
     * @return bool True if allowed, false otherwise
     */
    private function canView(User $user, CompanyOwnedInterface $entity): bool
    {
        // All authenticated users can view entities in their company
        return true;
    }

    /**
     * Check if user can edit entity.
     *
     * @param User                  $user   Current user
     * @param CompanyOwnedInterface $entity Entity to edit
     *
     * @return bool True if allowed, false otherwise
     */
    private function canEdit(User $user, CompanyOwnedInterface $entity): bool
    {
        $className = get_class($entity);

        // Entity-specific edit permissions
        return match (true) {
            // Projects: CHEF_PROJET or higher
            str_contains($className, 'Project') => $user->isChefProjet() || $user->isManager(),

            // Orders: CHEF_PROJET or higher
            str_contains($className, 'Order') => $user->isChefProjet() || $user->isManager(),

            // Clients: CHEF_PROJET or higher
            str_contains($className, 'Client') => $user->isChefProjet() || $user->isManager(),

            // Users: MANAGER or higher
            str_contains($className, 'User') => $user->isManager(),

            // Contributors: MANAGER or higher
            str_contains($className, 'Contributor') => $user->isManager(),

            // Timesheets: Owner or CHEF_PROJET
            str_contains($className, 'Timesheet') => $this->canEditTimesheet($user, $entity),

            // Default: MANAGER or higher
            default => $user->isManager(),
        };
    }

    /**
     * Check if user can delete entity.
     *
     * @param User                  $user   Current user
     * @param CompanyOwnedInterface $entity Entity to delete
     *
     * @return bool True if allowed, false otherwise
     */
    private function canDelete(User $user, CompanyOwnedInterface $entity): bool
    {
        // Deletion requires MANAGER or higher for most entities
        return $user->isManager();
    }

    /**
     * Check if user can edit specific timesheet.
     *
     * @param User                  $user   Current user
     * @param CompanyOwnedInterface $entity Timesheet entity
     *
     * @return bool True if allowed, false otherwise
     */
    private function canEditTimesheet(User $user, CompanyOwnedInterface $entity): bool
    {
        // Timesheet owner can edit their own
        if (method_exists($entity, 'getContributor')) {
            $contributor = $entity->getContributor();

            if ($contributor && method_exists($contributor, 'getUser')) {
                $timesheetUser = $contributor->getUser();

                if ($timesheetUser && $timesheetUser->getId() === $user->getId()) {
                    return true;
                }
            }
        }

        // CHEF_PROJET or higher can edit any timesheet
        return $user->isChefProjet() || $user->isManager();
    }

    /**
     * Log tenant isolation violation for security audit.
     *
     * @param User                  $user               User attempting access
     * @param string                $attribute          Permission attribute
     * @param CompanyOwnedInterface $subject            Entity being accessed
     * @param int                   $attemptedCompanyId Company ID of the entity
     */
    private function logTenantViolation(
        User $user,
        string $attribute,
        CompanyOwnedInterface $subject,
        int $attemptedCompanyId
    ): void {
        $currentCompany = $this->companyContext->getCurrentCompany();

        $this->securityLogger->error('SECURITY: Tenant isolation violation detected', [
            'user_id'              => $user->getId(),
            'user_email'           => $user->getEmail(),
            'user_company_id'      => $currentCompany->getId(),
            'user_company_name'    => $currentCompany->getName(),
            'attempted_company_id' => $attemptedCompanyId,
            'attribute'            => $attribute,
            'entity_class'         => get_class($subject),
            'entity_id'            => method_exists($subject, 'getId') ? $subject->getId() : null,
            'timestamp'            => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Interface\CompanyOwnedInterface;
use App\Entity\User;
use App\Security\CompanyContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Base class for entity-specific voters that enforce the tenant + role +
 * ownership triplet (sprint-007 SEC-VOTERS-001).
 *
 * Subclasses implement:
 *   - `supports($attribute, $subject)` — declare attributes + entity class.
 *   - `voteOnRoleAndOwnership($attribute, $subject, $user)` — domain rules.
 *
 * This base class handles:
 *   - Tenant match check via CompanyContext (delegated logic identical to
 *     CompanyVoter).
 *   - Superadmin bypass with security log entry.
 *   - Cross-tenant violation logging.
 */
abstract class AbstractTenantAwareVoter extends Voter
{
    public function __construct(
        protected readonly CompanyContext $companyContext,
        protected readonly LoggerInterface $securityLogger,
    ) {
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof CompanyOwnedInterface) {
            return false;
        }

        // Tenant match check (mirrors CompanyVoter behavior).
        try {
            $currentCompany = $this->companyContext->getCurrentCompany();
        } catch (AccessDeniedException) {
            return false;
        }

        $subjectCompany = $subject->getCompany();
        if ($subjectCompany->getId() !== $currentCompany->getId()) {
            // Superadmin bypass with audit log.
            if ($user->isSuperAdmin()) {
                $this->securityLogger->warning('SUPERADMIN cross-tenant access (allowed)', [
                    'voter' => static::class,
                    'attribute' => $attribute,
                    'user_id' => $user->getId(),
                    'user_company_id' => $currentCompany->getId(),
                    'accessed_company_id' => $subjectCompany->getId(),
                    'entity_class' => $subject::class,
                    'entity_id' => method_exists($subject, 'getId') ? $subject->getId() : null,
                ]);

                return true;
            }

            $this->securityLogger->warning('Cross-tenant access denied', [
                'voter' => static::class,
                'attribute' => $attribute,
                'user_id' => $user->getId(),
                'user_company_id' => $currentCompany->getId(),
                'accessed_company_id' => $subjectCompany->getId(),
                'entity_class' => $subject::class,
                'entity_id' => method_exists($subject, 'getId') ? $subject->getId() : null,
            ]);

            return false;
        }

        // Same tenant: delegate to subclass for role + ownership decision.
        return $this->voteOnRoleAndOwnership($attribute, $subject, $user);
    }

    /**
     * Subclass-specific role + ownership check. Tenant has already been
     * verified at this point.
     */
    abstract protected function voteOnRoleAndOwnership(
        string $attribute,
        mixed $subject,
        User $user,
    ): bool;

    protected function userHasAnyRole(User $user, string ...$roles): bool
    {
        $userRoles = $user->getRoles();
        foreach ($roles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }
}

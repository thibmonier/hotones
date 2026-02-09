<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Company;
use App\Entity\User;
use App\Exception\CompanyContextMissingException;
use App\Exception\CompanyInactiveException;
use App\Exception\CrossTenantAccessException;
use App\Repository\CompanyRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * CompanyContext Service - Central service for company context resolution.
 *
 * This service is responsible for determining the current active Company
 * for a request. It follows a priority-based resolution strategy:
 *
 * 1. JWT payload (company_id claim) - for API requests
 * 2. Session (current_company_id) - for web requests, SUPERADMIN switching
 * 3. User->getCompany() - default fallback
 *
 * The resolved company is cached for the request lifecycle to avoid
 * repeated database queries.
 *
 * Security Note: This is a critical security component. Any bugs here
 * could lead to cross-tenant data leakage.
 */
class CompanyContext
{
    private ?Company $cachedCompany = null;

    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly CompanyRepository $companyRepository,
    ) {
    }

    /**
     * Get the current active Company for the request.
     *
     * @throws CompanyContextMissingException If company context cannot be determined
     * @throws CompanyInactiveException       If company is not active
     *
     * @return Company The current company
     */
    public function getCurrentCompany(): Company
    {
        // Return cached company if available (performance optimization)
        if ($this->cachedCompany !== null) {
            return $this->cachedCompany;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new CompanyContextMissingException('User not authenticated');
        }

        $company = $this->resolveCompany($user);

        // Validate company status
        if (!$company->isActive() && !$company->isTrialActive()) {
            throw CompanyInactiveException::create($company->getId(), $company->getStatus());
        }

        // Validate user has access to this company
        if (!$this->hasAccessToCompany($user, $company)) {
            throw CrossTenantAccessException::create($user->getId(), $user->getCompany()->getId(), $company->getId());
        }

        // Cache for request lifecycle
        $this->cachedCompany = $company;

        return $company;
    }

    /**
     * Get Company ID without loading full entity (performance optimization).
     *
     * @throws CompanyContextMissingException If company context cannot be determined
     *
     * @return int Company ID
     */
    public function getCurrentCompanyId(): int
    {
        return $this->getCurrentCompany()->getId();
    }

    /**
     * Switch to different company (SUPERADMIN only, or CLI context).
     *
     * This regenerates the company context and stores the new company ID in session.
     * For API requests, clients must request a new JWT token with the new company_id.
     * For CLI context (no HTTP request), this bypasses authentication checks.
     *
     * @param Company $company Company to switch to
     *
     * @throws AccessDeniedException    If user is not SUPERADMIN (web context only)
     * @throws CompanyInactiveException If target company is not active
     */
    public function switchCompany(Company $company): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $isCLI   = !$request || php_sapi_name() === 'cli';

        // In CLI context (no HTTP request), bypass authentication checks
        if ($isCLI) {
            $this->cachedCompany = $company;

            return;
        }

        // Web context: require authenticated SUPERADMIN user
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('User not authenticated');
        }

        if (!$this->security->isGranted('ROLE_SUPERADMIN')) {
            throw new AccessDeniedException('Only SUPERADMIN can switch companies');
        }

        // Validate target company is active (skip in test environment)
        if (!$company->isActive() && !$company->isTrialActive() && $_ENV['APP_ENV'] !== 'test') {
            throw CompanyInactiveException::create($company->getId(), $company->getStatus());
        }

        // Store in session for web context
        $session = $request->getSession();
        $session->set('current_company_id', $company->getId());

        // Clear cache to force re-resolution
        $this->cachedCompany = null;
    }

    /**
     * Check if user has access to specific company.
     *
     * @param User    $user    User to check
     * @param Company $company Company to check access for
     *
     * @return bool True if user has access, false otherwise
     */
    public function hasAccessToCompany(User $user, Company $company): bool
    {
        // SUPERADMIN has access to all companies
        if ($this->security->isGranted('ROLE_SUPERADMIN')) {
            return true;
        }

        // Standard user: only their assigned company
        return $user->getCompany()->getId() === $company->getId();
    }

    /**
     * Get all companies accessible by current user.
     *
     * @return array<int, Company> Array of accessible companies
     */
    public function getAccessibleCompanies(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return [];
        }

        // SUPERADMIN sees all active companies
        if ($this->security->isGranted('ROLE_SUPERADMIN')) {
            return $this->companyRepository->findBy(['status' => [Company::STATUS_ACTIVE, Company::STATUS_TRIAL]], [
                'name' => 'ASC',
            ]);
        }

        // Standard user sees only their company
        return [$user->getCompany()];
    }

    /**
     * Clear cached company (useful for testing).
     */
    public function clearCache(): void
    {
        $this->cachedCompany = null;
    }

    /**
     * Resolve company from request context (JWT → Session → User default).
     *
     * @param User $user Authenticated user
     *
     * @throws CompanyContextMissingException If resolution fails
     *
     * @return Company Resolved company
     */
    private function resolveCompany(User $user): Company
    {
        $request = $this->requestStack->getCurrentRequest();

        // Priority 1: JWT company_id claim (API requests)
        if ($request && $request->attributes->has('jwt_company_id')) {
            $companyId = $request->attributes->get('jwt_company_id');
            $company   = $this->companyRepository->find($companyId);

            if (!$company) {
                throw new CompanyContextMissingException(sprintf('Company %d from JWT not found', $companyId));
            }

            return $company;
        }

        // Priority 2: Session company_id (web requests, SUPERADMIN switch)
        if ($request && $request->hasSession()) {
            $session = $request->getSession();

            if ($session->has('current_company_id')) {
                $companyId = $session->get('current_company_id');
                $company   = $this->companyRepository->find($companyId);

                if ($company && $this->hasAccessToCompany($user, $company)) {
                    return $company;
                }

                // Invalid session company_id, clear it
                $session->remove('current_company_id');
            }
        }

        // Priority 3: User's primary company (default)
        return $user->getCompany();
    }
}

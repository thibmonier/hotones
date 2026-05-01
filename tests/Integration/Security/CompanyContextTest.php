<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security;

use App\Entity\Company;
use App\Entity\User;
use App\Exception\CompanyContextMissingException;
use App\Security\CompanyContext;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Integration tests for CompanyContext.
 *
 * Critical security boundary: resolves the active tenant for
 * each request. Cross-tenant leaks start here if the resolution
 * priority (JWT > session > user.company) is wrong.
 *
 * Real DB required — DAMA rollback per test.
 */
final class CompanyContextTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CompanyContext $companyContext;
    private Company $companyAlpha;
    private Company $companyBeta;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->companyContext = static::getContainer()->get(CompanyContext::class);
        $this->entityManager  = static::getContainer()->get(EntityManagerInterface::class);

        $this->companyAlpha = $this->createCompany('Company Alpha', 'company-alpha');
        $this->companyBeta  = $this->createCompany('Company Beta', 'company-beta');

        $this->entityManager->flush();
    }

    #[Test]
    public function getCurrentCompany_returns_user_primary_company_by_default(): void
    {
        $this->authenticateUserInCompany($this->companyAlpha, ['ROLE_USER']);
        $this->companyContext->clearCache();

        $resolved = $this->companyContext->getCurrentCompany();

        self::assertSame($this->companyAlpha->getId(), $resolved->getId());
    }

    #[Test]
    public function getCurrentCompanyId_is_consistent_with_getCurrentCompany(): void
    {
        $this->authenticateUserInCompany($this->companyAlpha, ['ROLE_USER']);
        $this->companyContext->clearCache();

        self::assertSame(
            $this->companyContext->getCurrentCompany()->getId(),
            $this->companyContext->getCurrentCompanyId(),
        );
    }

    #[Test]
    public function getCurrentCompany_throws_if_user_not_authenticated(): void
    {
        // No token is stored — simulate an anonymous request.
        $tokenStorage = static::getContainer()->get(TokenStorageInterface::class);
        $tokenStorage->setToken(null);
        $this->companyContext->clearCache();

        $this->expectException(CompanyContextMissingException::class);

        $this->companyContext->getCurrentCompany();
    }

    #[Test]
    public function hasAccessToCompany_returns_true_for_user_own_company(): void
    {
        $user = $this->authenticateUserInCompany($this->companyAlpha, ['ROLE_USER']);

        self::assertTrue($this->companyContext->hasAccessToCompany($user, $this->companyAlpha));
    }

    #[Test]
    public function hasAccessToCompany_returns_false_for_standard_user_on_other_company(): void
    {
        $user = $this->authenticateUserInCompany($this->companyAlpha, ['ROLE_USER']);

        self::assertFalse($this->companyContext->hasAccessToCompany($user, $this->companyBeta));
    }

    #[Test]
    public function hasAccessToCompany_returns_true_for_superadmin_on_any_company(): void
    {
        $user = $this->authenticateUserInCompany($this->companyAlpha, ['ROLE_USER', 'ROLE_SUPERADMIN']);

        self::assertTrue($this->companyContext->hasAccessToCompany($user, $this->companyBeta));
    }

    #[Test]
    public function switchCompany_in_cli_bypasses_authentication_checks(): void
    {
        // CompanyContext detects CLI via php_sapi_name() and skips auth.
        // Purposely do NOT authenticate.
        $tokenStorage = static::getContainer()->get(TokenStorageInterface::class);
        $tokenStorage->setToken(null);
        $this->companyContext->clearCache();

        $this->companyContext->switchCompany($this->companyBeta);

        self::assertSame($this->companyBeta->getId(), $this->companyContext->getCurrentCompany()->getId());
    }

    #[Test]
    public function getAccessibleCompanies_returns_only_user_company_for_standard_user(): void
    {
        $this->authenticateUserInCompany($this->companyAlpha, ['ROLE_USER']);

        $companies = $this->companyContext->getAccessibleCompanies();

        self::assertCount(1, $companies);
        self::assertSame($this->companyAlpha->getId(), $companies[0]->getId());
    }

    #[Test]
    public function getAccessibleCompanies_returns_all_active_companies_for_superadmin(): void
    {
        $this->authenticateUserInCompany($this->companyAlpha, ['ROLE_USER', 'ROLE_SUPERADMIN']);

        $companies = $this->companyContext->getAccessibleCompanies();

        $ids = array_map(static fn (Company $c): int => $c->getId(), $companies);
        self::assertContains($this->companyAlpha->getId(), $ids);
        self::assertContains($this->companyBeta->getId(), $ids);
    }

    #[Test]
    public function clearCache_forces_re_resolution(): void
    {
        $this->authenticateUserInCompany($this->companyAlpha, ['ROLE_USER', 'ROLE_SUPERADMIN']);
        $this->companyContext->clearCache();

        $first = $this->companyContext->getCurrentCompany();
        self::assertSame($this->companyAlpha->getId(), $first->getId());

        // Switch via CLI, then clear — getCurrentCompany should reflect new company.
        $this->companyContext->switchCompany($this->companyBeta);
        $this->companyContext->clearCache();

        $second = $this->companyContext->getCurrentCompany();
        // Note: after clearCache, resolution falls back to user's primary company (Alpha),
        // because session priority path is not triggered in CLI. This documents the
        // intended behaviour of clearCache + CLI switch.
        self::assertSame($this->companyAlpha->getId(), $second->getId());
    }

    private function createCompany(string $name, string $slug): Company
    {
        $company = new Company();
        $company->setName($name);
        $company->setSlug($slug);
        $company->setSubscriptionTier(Company::TIER_PROFESSIONAL);
        $company->setCurrency('EUR');
        $company->setStructureCostCoefficient('1.35');
        $company->setEmployerChargesCoefficient('1.45');
        $company->setAnnualPaidLeaveDays(25);
        $company->setAnnualRttDays(10);
        $company->setBillingDayOfMonth(1);
        $company->setBillingStartDate(new DateTime());

        $this->entityManager->persist($company);

        return $company;
    }

    /**
     * @param string[] $roles
     */
    private function authenticateUserInCompany(Company $company, array $roles): User
    {
        $user = new User();
        $user->setCompany($company);
        $user->setEmail('test-'.uniqid().'@test.com');
        $user->setPassword('password');
        $user->firstName = 'Test';
        $user->lastName  = 'User';
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token        = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage = static::getContainer()->get(TokenStorageInterface::class);
        $tokenStorage->setToken($token);

        return $user;
    }
}

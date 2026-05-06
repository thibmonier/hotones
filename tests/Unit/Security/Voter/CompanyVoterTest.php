<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Company;
use App\Entity\Interface\CompanyOwnedInterface;
use App\Entity\User;
use App\Security\CompanyContext;
use App\Security\Voter\CompanyVoter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use stdClass;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Unit tests for CompanyVoter.
 *
 * Critical security boundary: enforces tenant isolation across
 * the whole multi-tenant application. Any regression here is a
 * potential data-leak between companies.
 *
 * Covers:
 * - supports() attribute/subject matrix
 * - voteOnAttribute tenant isolation logic (same vs different company)
 * - SUPERADMIN bypass + audit logging
 * - role-based CRUD authorization for entity classes
 */
final class CompanyVoterTest extends TestCase
{
    private CompanyContext&Stub $companyContext;
    private LoggerInterface&MockObject $securityLogger;
    private CompanyVoter $voter;

    protected function setUp(): void
    {
        $this->companyContext = $this->createStub(CompanyContext::class);
        $this->securityLogger = $this->createMock(LoggerInterface::class);
        $this->voter = new CompanyVoter($this->companyContext, $this->securityLogger);
    }

    #[Test]
    public function supportsReturnsTrueForViewOnCompanyOwnedSubject(): void
    {
        $subject = $this->createStub(CompanyOwnedInterface::class);

        $result = $this->invokeSupports(CompanyVoter::VIEW, $subject);

        self::assertTrue($result);
    }

    #[Test]
    public function supportsReturnsTrueForEditOnCompanyOwnedSubject(): void
    {
        $subject = $this->createStub(CompanyOwnedInterface::class);
        self::assertTrue($this->invokeSupports(CompanyVoter::EDIT, $subject));
    }

    #[Test]
    public function supportsReturnsTrueForDeleteOnCompanyOwnedSubject(): void
    {
        $subject = $this->createStub(CompanyOwnedInterface::class);
        self::assertTrue($this->invokeSupports(CompanyVoter::DELETE, $subject));
    }

    #[Test]
    public function supportsReturnsFalseForUnsupportedAttribute(): void
    {
        $subject = $this->createStub(CompanyOwnedInterface::class);
        self::assertFalse($this->invokeSupports('UNKNOWN', $subject));
    }

    #[Test]
    public function supportsReturnsFalseForNonCompanyOwnedSubject(): void
    {
        self::assertFalse($this->invokeSupports(CompanyVoter::VIEW, new stdClass()));
    }

    #[Test]
    public function voteDeniesAccessWhenSubjectBelongsToDifferentCompany(): void
    {
        $userCompany = $this->makeCompany(1);
        $subjectCompany = $this->makeCompany(2);
        $user = $this->makeUser(42, $userCompany, ['ROLE_USER']);
        $subject = $this->makeSubject($subjectCompany);

        $this->companyContext->method('getCurrentCompany')->willReturn($userCompany);

        // Expect audit log on tenant violation
        $this->securityLogger
            ->expects(self::once())
            ->method('error')
            ->with(self::stringContains('Tenant isolation violation'), self::anything());

        $token = $this->makeToken($user);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, [CompanyVoter::VIEW]));
    }

    #[Test]
    public function voteAllowsSuperadminCrossTenantAccessWithWarningLog(): void
    {
        $userCompany = $this->makeCompany(1);
        $subjectCompany = $this->makeCompany(2);
        $user = $this->makeUser(1, $userCompany, ['ROLE_SUPERADMIN'], isSuperAdmin: true);
        $subject = $this->makeSubject($subjectCompany);

        $this->companyContext->method('getCurrentCompany')->willReturn($userCompany);

        // Tenant violation is logged as error
        $this->securityLogger->expects(self::once())->method('error');
        // SUPERADMIN bypass is logged as warning
        $this->securityLogger
            ->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('SUPERADMIN cross-tenant access'), self::anything());

        $token = $this->makeToken($user);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, [CompanyVoter::VIEW]));
    }

    #[Test]
    public function voteAllowsViewForAnyAuthenticatedUserInSameCompany(): void
    {
        $company = $this->makeCompany(1);
        $user = $this->makeUser(42, $company, ['ROLE_USER']);
        $subject = $this->makeSubject($company);

        $this->companyContext->method('getCurrentCompany')->willReturn($company);
        $this->securityLogger->expects(self::never())->method('error');

        $token = $this->makeToken($user);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, [CompanyVoter::VIEW]));
    }

    #[Test]
    public function voteDeniesEditForIntervenantOnProject(): void
    {
        $company = $this->makeCompany(1);
        $user = $this->makeUser(42, $company, ['ROLE_INTERVENANT'], isChefProjet: false, isManager: false);
        $subject = $this->makeSubject($company, className: 'App\\Entity\\Project');

        $this->companyContext->method('getCurrentCompany')->willReturn($company);

        $token = $this->makeToken($user);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, [CompanyVoter::EDIT]));
    }

    #[Test]
    public function voteAllowsEditForManagerOnProject(): void
    {
        $company = $this->makeCompany(1);
        $user = $this->makeUser(42, $company, ['ROLE_MANAGER'], isChefProjet: true, isManager: true);
        $subject = $this->makeSubject($company, className: 'App\\Entity\\Project');

        $this->companyContext->method('getCurrentCompany')->willReturn($company);

        $token = $this->makeToken($user);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, [CompanyVoter::EDIT]));
    }

    #[Test]
    public function voteAllowsEditForChefProjetOnOrder(): void
    {
        $company = $this->makeCompany(1);
        $user = $this->makeUser(42, $company, ['ROLE_CHEF_PROJET'], isChefProjet: true, isManager: false);
        $subject = $this->makeSubject($company, className: 'App\\Entity\\Order');

        $this->companyContext->method('getCurrentCompany')->willReturn($company);

        $token = $this->makeToken($user);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, [CompanyVoter::EDIT]));
    }

    #[Test]
    public function voteDeniesDeleteForChefProjet(): void
    {
        $company = $this->makeCompany(1);
        $user = $this->makeUser(42, $company, ['ROLE_CHEF_PROJET'], isChefProjet: true, isManager: false);
        $subject = $this->makeSubject($company);

        $this->companyContext->method('getCurrentCompany')->willReturn($company);

        $token = $this->makeToken($user);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, [CompanyVoter::DELETE]));
    }

    #[Test]
    public function voteAllowsDeleteForManager(): void
    {
        $company = $this->makeCompany(1);
        $user = $this->makeUser(42, $company, ['ROLE_MANAGER'], isManager: true);
        $subject = $this->makeSubject($company);

        $this->companyContext->method('getCurrentCompany')->willReturn($company);

        $token = $this->makeToken($user);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, [CompanyVoter::DELETE]));
    }

    #[Test]
    public function voteAbstainsIfTokenUserIsNotAppUser(): void
    {
        $subject = $this->createStub(CompanyOwnedInterface::class);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        // Voter::vote returns ABSTAIN when voteOnAttribute returns false
        // and the attribute is supported but user is not App\Entity\User
        self::assertNotSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, [CompanyVoter::VIEW]));
    }

    /**
     * Invoke the protected supports() method via reflection.
     */
    private function invokeSupports(string $attribute, mixed $subject): bool
    {
        $reflection = new ReflectionMethod($this->voter, 'supports');

        return (bool) $reflection->invoke($this->voter, $attribute, $subject);
    }

    private function makeCompany(int $id): Company&Stub
    {
        $company = $this->createStub(Company::class);
        $company->method('getId')->willReturn($id);
        $company->method('getName')->willReturn('Company '.$id);

        return $company;
    }

    /**
     * @param string[] $roles
     */
    private function makeUser(
        int $id,
        Company $company,
        array $roles,
        bool $isSuperAdmin = false,
        bool $isChefProjet = false,
        bool $isManager = false,
    ): User&Stub {
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getEmail')->willReturn('user'.$id.'@test.com');
        $user->method('getRoles')->willReturn($roles);
        $user->method('getCompany')->willReturn($company);
        $user->method('isSuperAdmin')->willReturn($isSuperAdmin);
        $user->method('isChefProjet')->willReturn($isChefProjet);
        $user->method('isManager')->willReturn($isManager);

        return $user;
    }

    private function makeSubject(Company $company, string $className = ''): CompanyOwnedInterface&MockObject
    {
        // Using getMockBuilder to allow setMockClassName for FQCN simulation.
        $builder = $this->getMockBuilder(CompanyOwnedInterface::class);
        if ($className !== '') {
            // The actual class returned by $subject::class will be the mock class itself,
            // but CompanyVoter uses str_contains() on the class name. We match by
            // generating a mock whose class name contains the target token, and we
            // add a unique suffix to avoid class-name collisions across tests.
            $mockName = str_replace('\\', '_', $className).'Mock_'.uniqid();
            $builder = $builder->setMockClassName($mockName);
        }
        $subject = $builder->getMock();
        $subject->method('getCompany')->willReturn($company);

        return $subject;
    }

    private function makeToken(User $user): TokenInterface&Stub
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}

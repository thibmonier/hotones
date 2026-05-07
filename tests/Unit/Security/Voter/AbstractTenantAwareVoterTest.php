<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Company;
use App\Entity\Interface\CompanyOwnedInterface;
use App\Entity\User;
use App\Security\CompanyContext;
use App\Security\Voter\AbstractTenantAwareVoter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

#[AllowMockObjectsWithoutExpectations]
final class AbstractTenantAwareVoterTest extends TestCase
{
    private function makeCompany(int $id): Company
    {
        $company = new Company();
        $reflection = new ReflectionProperty(Company::class, 'id');
        $reflection->setValue($company, $id);

        return $company;
    }

    private function makeUserWithCompany(Company $company, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setCompany($company);
        $user->setRoles($roles);

        return $user;
    }

    private function makeSubject(Company $company): CompanyOwnedInterface
    {
        return new class($company) implements CompanyOwnedInterface {
            public function __construct(
                private Company $company,
            ) {
            }

            public function getCompany(): Company
            {
                return $this->company;
            }

            public function setCompany(Company $company): self
            {
                $this->company = $company;

                return $this;
            }

            public function getId(): int
            {
                return 1;
            }
        };
    }

    private function makeVoter(CompanyContext $companyContext): AbstractTenantAwareVoter
    {
        return new class($companyContext, new NullLogger()) extends AbstractTenantAwareVoter {
            protected function supports(string $attribute, mixed $subject): bool
            {
                return $attribute === 'TEST_VIEW' && $subject instanceof CompanyOwnedInterface;
            }

            protected function voteOnRoleAndOwnership(string $attribute, mixed $subject, User $user): bool
            {
                return true; // Always allow when tenant matches.
            }
        };
    }

    public function testRejectsNonUser(): void
    {
        $company = $this->makeCompany(1);
        $context = $this->createStub(CompanyContext::class);
        $context->method('getCurrentCompany')->willReturn($company);

        $voter = $this->makeVoter($context);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $vote = $voter->vote($token, $this->makeSubject($company), ['TEST_VIEW']);

        $this->assertSame(\Symfony\Component\Security\Core\Authorization\Voter\VoterInterface::ACCESS_DENIED, $vote);
    }

    public function testAllowsSameTenant(): void
    {
        $company = $this->makeCompany(42);
        $user = $this->makeUserWithCompany($company);
        $context = $this->createStub(CompanyContext::class);
        $context->method('getCurrentCompany')->willReturn($company);

        $voter = $this->makeVoter($context);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $vote = $voter->vote($token, $this->makeSubject($company), ['TEST_VIEW']);

        $this->assertSame(\Symfony\Component\Security\Core\Authorization\Voter\VoterInterface::ACCESS_GRANTED, $vote);
    }

    public function testDeniesCrossTenant(): void
    {
        $companyA = $this->makeCompany(1);
        $companyB = $this->makeCompany(2);
        $user = $this->makeUserWithCompany($companyA);

        $context = $this->createStub(CompanyContext::class);
        $context->method('getCurrentCompany')->willReturn($companyA);

        $voter = $this->makeVoter($context);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $vote = $voter->vote($token, $this->makeSubject($companyB), ['TEST_VIEW']);

        $this->assertSame(\Symfony\Component\Security\Core\Authorization\Voter\VoterInterface::ACCESS_DENIED, $vote);
    }

    public function testSuperAdminBypassesCrossTenant(): void
    {
        $companyA = $this->makeCompany(1);
        $companyB = $this->makeCompany(2);
        $user = $this->makeUserWithCompany($companyA, ['ROLE_SUPERADMIN']);

        $context = $this->createStub(CompanyContext::class);
        $context->method('getCurrentCompany')->willReturn($companyA);

        $voter = $this->makeVoter($context);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $vote = $voter->vote($token, $this->makeSubject($companyB), ['TEST_VIEW']);

        $this->assertSame(\Symfony\Component\Security\Core\Authorization\Voter\VoterInterface::ACCESS_GRANTED, $vote);
    }
}

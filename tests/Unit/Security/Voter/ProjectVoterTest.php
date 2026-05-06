<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Company;
use App\Entity\Project;
use App\Entity\User;
use App\Security\CompanyContext;
use App\Security\Voter\ProjectVoter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[AllowMockObjectsWithoutExpectations]
final class ProjectVoterTest extends TestCase
{
    private function makeCompany(int $id): Company
    {
        $company = new Company();
        (new ReflectionProperty(Company::class, 'id'))->setValue($company, $id);

        return $company;
    }

    private function makeUser(Company $company, array $roles, ?int $userId = 1): User
    {
        $user = new User();
        $user->setCompany($company);
        $user->setRoles($roles);
        if ($userId !== null) {
            (new ReflectionProperty(User::class, 'id'))->setValue($user, $userId);
        }

        return $user;
    }

    private function makeProject(Company $company, ?User $manager = null): Project
    {
        $project = new Project();
        $project->setCompany($company);
        if ($manager !== null) {
            $project->setProjectManager($manager);
        }

        return $project;
    }

    private function vote(User $user, Project $project, string $attribute): int
    {
        $context = $this->createMock(CompanyContext::class);
        $context->method('getCurrentCompany')->willReturn($user->getCompany());

        $voter = new ProjectVoter($context, new NullLogger());

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $voter->vote($token, $project, [$attribute]);
    }

    public function testViewIsGrantedToAnyTenantMember(): void
    {
        $company = $this->makeCompany(1);
        $user = $this->makeUser($company, ['ROLE_USER']);
        $project = $this->makeProject($company);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $project, ProjectVoter::VIEW));
    }

    public function testEditGrantedForAdmin(): void
    {
        $company = $this->makeCompany(1);
        $user = $this->makeUser($company, ['ROLE_ADMIN']);
        $project = $this->makeProject($company);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $project, ProjectVoter::EDIT));
    }

    public function testEditGrantedForChefDeProjetWhenAssignedAsManager(): void
    {
        $company = $this->makeCompany(1);
        $user = $this->makeUser($company, ['ROLE_CHEF_PROJET'], userId: 7);
        $project = $this->makeProject($company, $user);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $project, ProjectVoter::EDIT));
    }

    public function testEditDeniedForChefDeProjetNotAssigned(): void
    {
        $company = $this->makeCompany(1);
        $user = $this->makeUser($company, ['ROLE_CHEF_PROJET'], userId: 7);
        $otherManager = $this->makeUser($company, ['ROLE_CHEF_PROJET'], userId: 99);
        $project = $this->makeProject($company, $otherManager);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($user, $project, ProjectVoter::EDIT));
    }

    public function testDeleteGrantedForAdminOnly(): void
    {
        $company = $this->makeCompany(1);
        $project = $this->makeProject($company);

        $admin = $this->makeUser($company, ['ROLE_ADMIN']);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($admin, $project, ProjectVoter::DELETE));

        $manager = $this->makeUser($company, ['ROLE_MANAGER']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($manager, $project, ProjectVoter::DELETE));
    }
}

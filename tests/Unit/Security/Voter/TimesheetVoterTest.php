<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Security\CompanyContext;
use App\Security\Voter\TimesheetVoter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[AllowMockObjectsWithoutExpectations]
final class TimesheetVoterTest extends TestCase
{
    private function makeCompany(int $id = 1): Company
    {
        $company = new Company();
        (new ReflectionProperty(Company::class, 'id'))->setValue($company, $id);

        return $company;
    }

    private function makeUser(Company $company, array $roles, int $id = 1): User
    {
        $user = new User();
        $user->setCompany($company);
        $user->setRoles($roles);
        (new ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    private function makeTimesheet(Company $company, ?User $contributorUser): Timesheet
    {
        $contributor = new Contributor();
        $contributor->setCompany($company);
        if ($contributorUser !== null) {
            $contributor->setUser($contributorUser);
        }
        $contributor->setFirstName('X');
        $contributor->setLastName('Y');

        $timesheet = $this->createPartialMock(Timesheet::class, ['getContributor', 'getCompany']);
        $timesheet->method('getContributor')->willReturn($contributor);
        $timesheet->method('getCompany')->willReturn($company);

        return $timesheet;
    }

    private function vote(User $user, Timesheet $timesheet, string $attribute): int
    {
        $context = $this->createMock(CompanyContext::class);
        $context->method('getCurrentCompany')->willReturn($user->getCompany());

        $voter = new TimesheetVoter($context, new NullLogger());

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $voter->vote($token, $timesheet, [$attribute]);
    }

    public function testOwnerCanView(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 7);
        $timesheet = $this->makeTimesheet($company, $owner);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($owner, $timesheet, TimesheetVoter::VIEW));
    }

    public function testManagerCanView(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 7);
        $manager = $this->makeUser($company, ['ROLE_MANAGER'], id: 99);
        $timesheet = $this->makeTimesheet($company, $owner);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($manager, $timesheet, TimesheetVoter::VIEW));
    }

    public function testNonOwnerInterventantCannotView(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 7);
        $other = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 99);
        $timesheet = $this->makeTimesheet($company, $owner);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($other, $timesheet, TimesheetVoter::VIEW));
    }

    public function testOwnerCannotValidateOwnTimesheet(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT', 'ROLE_CHEF_PROJET'], id: 7);
        $timesheet = $this->makeTimesheet($company, $owner);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($owner, $timesheet, TimesheetVoter::VALIDATE));
    }

    public function testManagerCanValidateOthersTimesheet(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 7);
        $manager = $this->makeUser($company, ['ROLE_MANAGER'], id: 99);
        $timesheet = $this->makeTimesheet($company, $owner);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($manager, $timesheet, TimesheetVoter::VALIDATE));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Security\CompanyContext;
use App\Security\Voter\ClientVoter;
use App\Security\Voter\ContributorVoter;
use App\Security\Voter\ExpenseReportVoter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Combined coverage of ClientVoter, ContributorVoter, ExpenseReportVoter.
 * Same triplet base class — share a single fixture file.
 */
#[AllowMockObjectsWithoutExpectations]
final class ClientContributorExpenseVoterTest extends TestCase
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

    private function ctx(User $user): CompanyContext
    {
        $context = $this->createMock(CompanyContext::class);
        $context->method('getCurrentCompany')->willReturn($user->getCompany());

        return $context;
    }

    private function token(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    // -----------------------------------------------------------------
    // ClientVoter
    // -----------------------------------------------------------------

    public function testClientViewGrantedAnyTenantMember(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company, ['ROLE_USER']);
        $client = new Client();
        $client->setCompany($company);

        $voter = new ClientVoter($this->ctx($user), new NullLogger());

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($user), $client, [ClientVoter::VIEW]));
    }

    public function testClientEditDeniedForBasicUser(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company, ['ROLE_INTERVENANT']);
        $client = new Client();
        $client->setCompany($company);

        $voter = new ClientVoter($this->ctx($user), new NullLogger());

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token($user), $client, [ClientVoter::EDIT]));
    }

    public function testClientEditGrantedForCommercial(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company, ['ROLE_COMMERCIAL']);
        $client = new Client();
        $client->setCompany($company);

        $voter = new ClientVoter($this->ctx($user), new NullLogger());

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($user), $client, [ClientVoter::EDIT]));
    }

    public function testClientDeleteAdminOnly(): void
    {
        $company = $this->makeCompany();
        $client = new Client();
        $client->setCompany($company);

        $manager = $this->makeUser($company, ['ROLE_MANAGER']);
        $voterManager = new ClientVoter($this->ctx($manager), new NullLogger());
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voterManager->vote($this->token($manager), $client, [ClientVoter::DELETE]));

        $admin = $this->makeUser($company, ['ROLE_ADMIN']);
        $voterAdmin = new ClientVoter($this->ctx($admin), new NullLogger());
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voterAdmin->vote($this->token($admin), $client, [ClientVoter::DELETE]));
    }

    // -----------------------------------------------------------------
    // ContributorVoter
    // -----------------------------------------------------------------

    public function testContributorSelfCanView(): void
    {
        $company = $this->makeCompany();
        $self = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 7);
        $contributor = new Contributor();
        $contributor->setCompany($company);
        $contributor->setUser($self);
        $contributor->setFirstName('A');
        $contributor->setLastName('B');

        $voter = new ContributorVoter($this->ctx($self), new NullLogger());

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($self), $contributor, [ContributorVoter::VIEW]));
    }

    public function testContributorSelfCannotDeactivate(): void
    {
        $company = $this->makeCompany();
        $self = $this->makeUser($company, ['ROLE_INTERVENANT', 'ROLE_MANAGER'], id: 7);
        $contributor = new Contributor();
        $contributor->setCompany($company);
        $contributor->setUser($self);
        $contributor->setFirstName('A');
        $contributor->setLastName('B');

        $voter = new ContributorVoter($this->ctx($self), new NullLogger());

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token($self), $contributor, [ContributorVoter::DEACTIVATE]));
    }

    public function testContributorOtherDeniedToBasicUser(): void
    {
        $company = $this->makeCompany();
        $self = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 7);
        $other = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 99);
        $contributor = new Contributor();
        $contributor->setCompany($company);
        $contributor->setUser($other);
        $contributor->setFirstName('X');
        $contributor->setLastName('Y');

        $voter = new ContributorVoter($this->ctx($self), new NullLogger());

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token($self), $contributor, [ContributorVoter::EDIT]));
    }

    // -----------------------------------------------------------------
    // ExpenseReportVoter
    // -----------------------------------------------------------------

    private function makeExpense(Company $company, ?User $owner, string $status = ExpenseReport::STATUS_DRAFT): ExpenseReport
    {
        $contributor = new Contributor();
        $contributor->setCompany($company);
        if ($owner !== null) {
            $contributor->setUser($owner);
        }
        $contributor->setFirstName('E');
        $contributor->setLastName('F');

        $report = $this->createPartialMock(ExpenseReport::class, ['getContributor', 'getCompany', 'getStatus']);
        $report->method('getContributor')->willReturn($contributor);
        $report->method('getCompany')->willReturn($company);
        $report->method('getStatus')->willReturn($status);

        return $report;
    }

    public function testExpenseEditOwnerOnDraft(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 7);
        $report = $this->makeExpense($company, $owner, ExpenseReport::STATUS_DRAFT);

        $voter = new ExpenseReportVoter($this->ctx($owner), new NullLogger());

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($owner), $report, [ExpenseReportVoter::EDIT]));
    }

    public function testExpenseEditDeniedAfterSubmit(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 7);
        $report = $this->makeExpense($company, $owner, ExpenseReport::STATUS_PENDING);

        $voter = new ExpenseReportVoter($this->ctx($owner), new NullLogger());

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token($owner), $report, [ExpenseReportVoter::EDIT]));
    }

    public function testExpenseApproveDeniedToOwner(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT', 'ROLE_COMPTA'], id: 7);
        $report = $this->makeExpense($company, $owner, ExpenseReport::STATUS_PENDING);

        $voter = new ExpenseReportVoter($this->ctx($owner), new NullLogger());

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token($owner), $report, [ExpenseReportVoter::APPROVE]));
    }

    public function testExpenseApproveGrantedToCompta(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 7);
        $compta = $this->makeUser($company, ['ROLE_COMPTA'], id: 99);
        $report = $this->makeExpense($company, $owner, ExpenseReport::STATUS_PENDING);

        $voter = new ExpenseReportVoter($this->ctx($compta), new NullLogger());

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($compta), $report, [ExpenseReportVoter::APPROVE]));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\ValueObject\VacationStatus;
use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\User;
use App\Security\CompanyContext;
use App\Security\Voter\VacationVoter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class VacationVoterTest extends TestCase
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

    private function makeVacation(Company $company, ?User $contributorUser, VacationStatus $status = VacationStatus::PENDING): Vacation
    {
        $contributor = new Contributor();
        $contributor->setCompany($company);
        if ($contributorUser !== null) {
            $contributor->setUser($contributorUser);
        }
        $contributor->setFirstName('A');
        $contributor->setLastName('B');

        // Vacation is a final DDD aggregate — cannot be mocked. Instantiate
        // empty via reflection and inject the fields voter tests need.
        $reflection = new ReflectionClass(Vacation::class);
        $vacation = $reflection->newInstanceWithoutConstructor();

        (new ReflectionProperty(Vacation::class, 'company'))->setValue($vacation, $company);
        (new ReflectionProperty(Vacation::class, 'contributor'))->setValue($vacation, $contributor);
        (new ReflectionProperty(Vacation::class, 'status'))->setValue($vacation, $status);

        return $vacation;
    }

    private function vote(User $user, Vacation $vacation, string $attribute): int
    {
        $context = $this->createMock(CompanyContext::class);
        $context->method('getCurrentCompany')->willReturn($user->getCompany());

        $voter = new VacationVoter($context, new NullLogger());

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $voter->vote($token, $vacation, [$attribute]);
    }

    public function testOwnerCannotSelfApprove(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT', 'ROLE_MANAGER'], id: 1);
        $vacation = $this->makeVacation($company, $owner);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($owner, $vacation, VacationVoter::APPROVE));
    }

    public function testManagerCanApproveOthers(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 1);
        $manager = $this->makeUser($company, ['ROLE_MANAGER'], id: 2);
        $vacation = $this->makeVacation($company, $owner);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($manager, $vacation, VacationVoter::APPROVE));
    }

    public function testOwnerCanCancelBeforeApproval(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 1);
        $vacation = $this->makeVacation($company, $owner, VacationStatus::PENDING);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($owner, $vacation, VacationVoter::CANCEL));
    }

    public function testOwnerCannotCancelAfterApproval(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 1);
        $vacation = $this->makeVacation($company, $owner, VacationStatus::APPROVED);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($owner, $vacation, VacationVoter::CANCEL));
    }

    public function testManagerCanCancelEvenAfterApproval(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeUser($company, ['ROLE_INTERVENANT'], id: 1);
        $manager = $this->makeUser($company, ['ROLE_MANAGER'], id: 2);
        $vacation = $this->makeVacation($company, $owner, VacationStatus::APPROVED);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($manager, $vacation, VacationVoter::CANCEL));
    }
}

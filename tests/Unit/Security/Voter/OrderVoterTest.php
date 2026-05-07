<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Company;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Security\CompanyContext;
use App\Security\Voter\OrderVoter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[AllowMockObjectsWithoutExpectations]
final class OrderVoterTest extends TestCase
{
    private function makeCompany(int $id = 1): Company
    {
        $company = new Company();
        new ReflectionProperty(Company::class, 'id')->setValue($company, $id);

        return $company;
    }

    private function makeUser(Company $company, array $roles): User
    {
        $user = new User();
        $user->setCompany($company);
        $user->setRoles($roles);

        return $user;
    }

    private function makeOrder(Company $company, string $status = 'a_signer'): Order
    {
        $order = new Order();
        $order->setCompany($company);
        $order->setStatus($status);

        return $order;
    }

    private function vote(User $user, Order $order, string $attribute): int
    {
        $context = $this->createStub(CompanyContext::class);
        $context->method('getCurrentCompany')->willReturn($user->getCompany());

        $voter = new OrderVoter($context, new NullLogger());

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $voter->vote($token, $order, [$attribute]);
    }

    public function testViewGrantedToAnyTenantMember(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company, ['ROLE_USER']);
        $order = $this->makeOrder($company);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $order, OrderVoter::VIEW));
    }

    public function testEditGrantedForCommercial(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company, ['ROLE_COMMERCIAL']);
        $order = $this->makeOrder($company, OrderStatus::PENDING->value);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $order, OrderVoter::EDIT));
    }

    public function testEditDeniedOnCompletedExceptSuperadmin(): void
    {
        $company = $this->makeCompany();
        $order = $this->makeOrder($company, OrderStatus::COMPLETED->value);

        $manager = $this->makeUser($company, ['ROLE_MANAGER']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($manager, $order, OrderVoter::EDIT));

        $superadmin = $this->makeUser($company, ['ROLE_SUPERADMIN']);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($superadmin, $order, OrderVoter::EDIT));
    }

    public function testSignGrantedFromPendingForManager(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company, ['ROLE_MANAGER']);
        $order = $this->makeOrder($company, OrderStatus::PENDING->value);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $order, OrderVoter::SIGN));
    }

    public function testSignDeniedFromLost(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company, ['ROLE_ADMIN']);
        $order = $this->makeOrder($company, OrderStatus::LOST->value);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($user, $order, OrderVoter::SIGN));
    }

    public function testSignDeniedForCommercial(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company, ['ROLE_COMMERCIAL']);
        $order = $this->makeOrder($company, OrderStatus::PENDING->value);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($user, $order, OrderVoter::SIGN));
    }

    public function testDeleteAdminOnly(): void
    {
        $company = $this->makeCompany();
        $order = $this->makeOrder($company);

        $admin = $this->makeUser($company, ['ROLE_ADMIN']);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($admin, $order, OrderVoter::DELETE));

        $cp = $this->makeUser($company, ['ROLE_CHEF_PROJET']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($cp, $order, OrderVoter::DELETE));
    }
}

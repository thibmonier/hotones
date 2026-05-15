<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Company;
use App\Entity\Invoice;
use App\Entity\User;
use App\Security\CompanyContext;
use App\Security\Voter\InvoiceVoter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[AllowMockObjectsWithoutExpectations]
final class InvoiceVoterTest extends TestCase
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

    private function makeInvoice(Company $company, string $status = Invoice::STATUS_DRAFT): Invoice
    {
        $invoice = new Invoice();
        $invoice->setCompany($company);
        $invoice->setStatus($status);

        return $invoice;
    }

    private function vote(User $user, Invoice $invoice, string $attribute): int
    {
        $context = $this->createStub(CompanyContext::class);
        $context->method('getCurrentCompany')->willReturn($user->getCompany());

        $voter = new InvoiceVoter($context, new NullLogger());

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $voter->vote($token, $invoice, [$attribute]);
    }

    public function testEditDeniedOnSentInvoice(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company, ['ROLE_COMPTA']);
        $invoice = $this->makeInvoice($company, Invoice::STATUS_SENT);

        static::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($user, $invoice, InvoiceVoter::EDIT));
    }

    public function testEditGrantedOnDraftForCompta(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company, ['ROLE_COMPTA']);
        $invoice = $this->makeInvoice($company, Invoice::STATUS_DRAFT);

        static::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $invoice, InvoiceVoter::EDIT));
    }

    public function testCancelGrantedOnSentForCompta(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company, ['ROLE_COMPTA']);
        $invoice = $this->makeInvoice($company, Invoice::STATUS_SENT);

        static::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $invoice, InvoiceVoter::CANCEL));
    }

    public function testCancelDeniedOnDraft(): void
    {
        $company = $this->makeCompany();
        $user = $this->makeUser($company, ['ROLE_COMPTA']);
        $invoice = $this->makeInvoice($company, Invoice::STATUS_DRAFT);

        static::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($user, $invoice, InvoiceVoter::CANCEL));
    }

    public function testDeleteOnlyOnDraftForAdmin(): void
    {
        $company = $this->makeCompany();
        $admin = $this->makeUser($company, ['ROLE_ADMIN']);

        $draft = $this->makeInvoice($company, Invoice::STATUS_DRAFT);
        static::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($admin, $draft, InvoiceVoter::DELETE));

        $sent = $this->makeInvoice($company, Invoice::STATUS_SENT);
        static::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($admin, $sent, InvoiceVoter::DELETE));
    }
}

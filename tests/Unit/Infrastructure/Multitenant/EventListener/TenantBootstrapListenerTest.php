<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Multitenant\EventListener;

use App\Entity\Company;
use App\Entity\User;
use App\Infrastructure\Multitenant\Doctrine\Filter\TenantFilter;
use App\Infrastructure\Multitenant\EventListener\TenantBootstrapListener;
use App\Infrastructure\Multitenant\TenantContext;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Unit tests for TenantBootstrapListener.
 *
 * Coverage strategy:
 *   - TenantContext mutation: tested via real TenantContext + getter assertion.
 *   - SQLFilter activation: cannot be mocked because Doctrine SQLFilter
 *     `setParameter()` and `getParameter()` are `final`. We instantiate a real
 *     TenantFilter with mocked EntityManager + Connection (matches the pattern
 *     established in TenantFilterTest).
 */
#[AllowMockObjectsWithoutExpectations]
final class TenantBootstrapListenerTest extends TestCase
{
    private function makeEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);

        return new RequestEvent($kernel, new Request(), $requestType);
    }

    private function makeCompanyWithId(int $id): Company
    {
        $company = new Company();
        $reflection = new ReflectionProperty(Company::class, 'id');
        $reflection->setValue($company, $id);

        return $company;
    }

    /**
     * @return array{0: EntityManagerInterface, 1: TenantFilter, 2: FilterCollection}
     */
    private function makeEmWithRealFilter(bool $alreadyEnabled = false): array
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('quote')->willReturnCallback(static fn ($value): string => "'".$value."'");

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        $filterCollection = $this->createMock(FilterCollection::class);
        $filterCollection->method('setFiltersStateDirty');

        $em->method('getFilters')->willReturn($filterCollection);

        $filter = new TenantFilter($em);

        $filterCollection->method('isEnabled')->willReturnCallback(static fn (string $name): bool => 'tenant_filter' === $name && $alreadyEnabled);
        $filterCollection->method('getFilter')->willReturnCallback(static fn (string $name): ?\App\Infrastructure\Multitenant\Doctrine\Filter\TenantFilter => 'tenant_filter' === $name
            ? $filter
            : null);

        return [$em, $filter, $filterCollection];
    }

    public function testSubRequestIsIgnored(): void
    {
        $security = $this->createMock(Security::class);
        $security->expects($this->never())->method('getUser');

        $tenantContext = new TenantContext();
        $em = $this->createMock(EntityManagerInterface::class);

        $listener = new TenantBootstrapListener($security, $tenantContext, $em);
        $listener($this->makeEvent(HttpKernelInterface::SUB_REQUEST));

        static::assertFalse($tenantContext->hasTenant());
    }

    public function testAnonymousUserDoesNotSetTenantOrTouchFilter(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $tenantContext = new TenantContext();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('getFilters');

        $listener = new TenantBootstrapListener($security, $tenantContext, $em);
        $listener($this->makeEvent());

        static::assertFalse($tenantContext->hasTenant());
    }

    public function testTransientCompanyWithoutIdIsIgnored(): void
    {
        // Edge case: User loaded with a transient Company (id null) — should
        // not crash the listener. Defensive path.
        $company = new Company(); // no id set
        $user = new User();
        $user->setCompany($company);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $tenantContext = new TenantContext();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('getFilters');

        $listener = new TenantBootstrapListener($security, $tenantContext, $em);
        $listener($this->makeEvent());

        static::assertFalse($tenantContext->hasTenant());
    }

    public function testAuthenticatedUserSetsTenantAndEnablesFilter(): void
    {
        $company = $this->makeCompanyWithId(42);
        $user = new User();
        $user->setCompany($company);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $tenantContext = new TenantContext();

        [$em, , $filterCollection] = $this->makeEmWithRealFilter(alreadyEnabled: false);
        $filterCollection->expects($this->once())->method('enable')->with('tenant_filter');

        $listener = new TenantBootstrapListener($security, $tenantContext, $em);
        $listener($this->makeEvent());

        static::assertTrue($tenantContext->hasTenant());
        static::assertSame(42, $tenantContext->getCurrentTenant()->value);
    }

    public function testFilterAlreadyEnabledIsNotReEnabled(): void
    {
        $company = $this->makeCompanyWithId(7);
        $user = new User();
        $user->setCompany($company);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $tenantContext = new TenantContext();

        [$em, , $filterCollection] = $this->makeEmWithRealFilter(alreadyEnabled: true);
        $filterCollection->expects($this->never())->method('enable');

        $listener = new TenantBootstrapListener($security, $tenantContext, $em);
        $listener($this->makeEvent());

        static::assertSame(7, $tenantContext->getCurrentTenant()->value);
    }
}

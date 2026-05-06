<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Multitenant\Doctrine\Filter;

use App\Domain\Shared\Tenant\TenantAwareInterface;
use App\Infrastructure\Multitenant\Doctrine\Filter\TenantFilter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\FilterCollection;
use PHPUnit\Framework\TestCase;
use stdClass;

final class TenantFilterTest extends TestCase
{
    /**
     * Build a TenantFilter with a mocked EM whose Connection emits quoted
     * tenant ids by wrapping the input in single quotes.
     *
     * Doctrine SQLFilter::getParameter() is final and goes through
     * `$em->getConnection()->quote()`, so we mock that path.
     */
    private function makeFilter(int $tenantId): TenantFilter
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('quote')->willReturnCallback(static fn ($value) => "'".(string) $value."'");

        $filterCollection = $this->createStub(FilterCollection::class);
        $filterCollection->method('setFiltersStateDirty');

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getFilters')->willReturn($filterCollection);

        $filter = new TenantFilter($em);
        $filter->setParameter('tenantId', (string) $tenantId);

        return $filter;
    }

    public function testNoConstraintForNonTenantAwareEntity(): void
    {
        $filter = $this->makeFilter(42);

        $metadata = $this->createStub(ClassMetadata::class);
        $metadata->method('getName')->willReturn(stdClass::class);

        $constraint = $filter->addFilterConstraint($metadata, 't');

        $this->assertSame('', $constraint);
    }

    public function testConstraintForTenantAwareEntityWithDefaultColumn(): void
    {
        $filter = $this->makeFilter(42);

        $tenantAwareEntity = new class implements TenantAwareInterface {};

        $metadata = $this->createStub(ClassMetadata::class);
        $metadata->method('getName')->willReturn($tenantAwareEntity::class);

        $constraint = $filter->addFilterConstraint($metadata, 'p');

        $this->assertSame("p.company_id = '42'", $constraint);
    }

    public function testConstraintUsesCustomAlias(): void
    {
        $filter = $this->makeFilter(7);

        $tenantAwareEntity = new class implements TenantAwareInterface {};

        $metadata = $this->createStub(ClassMetadata::class);
        $metadata->method('getName')->willReturn($tenantAwareEntity::class);

        $constraint = $filter->addFilterConstraint($metadata, 'order_alias');

        $this->assertSame("order_alias.company_id = '7'", $constraint);
    }

    public function testConstraintHandlesLargeTenantIds(): void
    {
        $filter = $this->makeFilter(987654321);

        $tenantAwareEntity = new class implements TenantAwareInterface {};

        $metadata = $this->createStub(ClassMetadata::class);
        $metadata->method('getName')->willReturn($tenantAwareEntity::class);

        $constraint = $filter->addFilterConstraint($metadata, 't');

        $this->assertSame("t.company_id = '987654321'", $constraint);
    }
}

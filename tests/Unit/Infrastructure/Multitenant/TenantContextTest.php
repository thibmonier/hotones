<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Multitenant;

use App\Domain\Shared\ValueObject\TenantId;
use App\Infrastructure\Multitenant\Exception\NoTenantContextException;
use App\Infrastructure\Multitenant\TenantContext;
use PHPUnit\Framework\TestCase;

final class TenantContextTest extends TestCase
{
    public function testHasTenantReturnsFalseInitially(): void
    {
        $context = new TenantContext();

        static::assertFalse($context->hasTenant());
    }

    public function testGetCurrentTenantThrowsWhenUnset(): void
    {
        $context = new TenantContext();

        $this->expectException(NoTenantContextException::class);
        $this->expectExceptionMessageMatches('/No tenant context/');

        $context->getCurrentTenant();
    }

    public function testSetCurrentTenantStoresValue(): void
    {
        $context = new TenantContext();
        $tenantId = TenantId::fromInt(42);

        $context->setCurrentTenant($tenantId);

        static::assertTrue($context->hasTenant());
        static::assertSame(42, $context->getCurrentTenant()->value);
    }

    public function testSetCurrentTenantOverwritesPrevious(): void
    {
        $context = new TenantContext();

        $context->setCurrentTenant(TenantId::fromInt(1));
        $context->setCurrentTenant(TenantId::fromInt(2));

        static::assertSame(2, $context->getCurrentTenant()->value);
    }

    public function testClearResetsContext(): void
    {
        $context = new TenantContext();
        $context->setCurrentTenant(TenantId::fromInt(7));

        $context->clear();

        static::assertFalse($context->hasTenant());
        $this->expectException(NoTenantContextException::class);
        $context->getCurrentTenant();
    }

    public function testGetCurrentTenantReturnsValueObject(): void
    {
        $context = new TenantContext();
        $original = TenantId::fromInt(99);
        $context->setCurrentTenant($original);

        $retrieved = $context->getCurrentTenant();

        static::assertTrue($retrieved->equals($original));
    }
}

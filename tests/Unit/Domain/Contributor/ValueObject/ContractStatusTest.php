<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Contributor\ValueObject;

use App\Domain\Contributor\ValueObject\ContractStatus;
use PHPUnit\Framework\TestCase;

final class ContractStatusTest extends TestCase
{
    public function testActive(): void
    {
        static::assertTrue(ContractStatus::ACTIVE->isActive());
        static::assertSame('Actif', ContractStatus::ACTIVE->getLabel());
    }

    public function testInactive(): void
    {
        static::assertFalse(ContractStatus::INACTIVE->isActive());
        static::assertSame('Inactif', ContractStatus::INACTIVE->getLabel());
    }

    public function testValues(): void
    {
        static::assertSame('active', ContractStatus::ACTIVE->value);
        static::assertSame('inactive', ContractStatus::INACTIVE->value);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Contributor\ValueObject;

use App\Domain\Contributor\ValueObject\ContractStatus;
use PHPUnit\Framework\TestCase;

final class ContractStatusTest extends TestCase
{
    public function testActive(): void
    {
        $this->assertTrue(ContractStatus::ACTIVE->isActive());
        $this->assertSame('Actif', ContractStatus::ACTIVE->getLabel());
    }

    public function testInactive(): void
    {
        $this->assertFalse(ContractStatus::INACTIVE->isActive());
        $this->assertSame('Inactif', ContractStatus::INACTIVE->getLabel());
    }

    public function testValues(): void
    {
        $this->assertSame('active', ContractStatus::ACTIVE->value);
        $this->assertSame('inactive', ContractStatus::INACTIVE->value);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\ValueObject;

use App\Domain\Order\ValueObject\ContractType;
use PHPUnit\Framework\TestCase;

final class ContractTypeTest extends TestCase
{
    public function testFixedPriceLabel(): void
    {
        $this->assertSame('Forfait', ContractType::FIXED_PRICE->getLabel());
    }

    public function testTimeAndMaterialLabel(): void
    {
        $this->assertSame('Régie', ContractType::TIME_AND_MATERIAL->getLabel());
    }

    public function testIsFixedPrice(): void
    {
        $this->assertTrue(ContractType::FIXED_PRICE->isFixedPrice());
        $this->assertFalse(ContractType::TIME_AND_MATERIAL->isFixedPrice());
    }

    public function testIsTimeAndMaterial(): void
    {
        $this->assertTrue(ContractType::TIME_AND_MATERIAL->isTimeAndMaterial());
        $this->assertFalse(ContractType::FIXED_PRICE->isTimeAndMaterial());
    }

    public function testFromValueRoundtrip(): void
    {
        $this->assertSame(ContractType::FIXED_PRICE, ContractType::from('forfait'));
        $this->assertSame(ContractType::TIME_AND_MATERIAL, ContractType::from('regie'));
    }
}

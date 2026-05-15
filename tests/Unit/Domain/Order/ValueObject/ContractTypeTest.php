<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\ValueObject;

use App\Domain\Order\ValueObject\ContractType;
use PHPUnit\Framework\TestCase;

final class ContractTypeTest extends TestCase
{
    public function testFixedPriceLabel(): void
    {
        static::assertSame('Forfait', ContractType::FIXED_PRICE->getLabel());
    }

    public function testTimeAndMaterialLabel(): void
    {
        static::assertSame('Régie', ContractType::TIME_AND_MATERIAL->getLabel());
    }

    public function testIsFixedPrice(): void
    {
        static::assertTrue(ContractType::FIXED_PRICE->isFixedPrice());
        static::assertFalse(ContractType::TIME_AND_MATERIAL->isFixedPrice());
    }

    public function testIsTimeAndMaterial(): void
    {
        static::assertTrue(ContractType::TIME_AND_MATERIAL->isTimeAndMaterial());
        static::assertFalse(ContractType::FIXED_PRICE->isTimeAndMaterial());
    }

    public function testFromValueRoundtrip(): void
    {
        static::assertSame(ContractType::FIXED_PRICE, ContractType::from('forfait'));
        static::assertSame(ContractType::TIME_AND_MATERIAL, ContractType::from('regie'));
    }
}

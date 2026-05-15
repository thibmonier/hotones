<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Order\Translator;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Order\Entity\Order as DddOrder;
use App\Domain\Order\ValueObject\ContractType;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Shared\ValueObject\Money;
use App\Entity\Order as FlatOrder;
use App\Infrastructure\Order\Translator\OrderDddToFlatTranslator;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class OrderDddToFlatTranslatorTest extends TestCase
{
    private function makeDdd(?OrderStatus $status = null, ContractType $type = ContractType::FIXED_PRICE): DddOrder
    {
        $order = DddOrder::create(
            OrderId::fromLegacyInt(42),
            'D202601-001',
            ClientId::fromLegacyInt(7),
            $type,
            Money::fromAmount(10_000),
        );
        if ($status !== null) {
            new ReflectionProperty(DddOrder::class, 'status')->setValue($order, $status);
        }

        return $order;
    }

    public function testApplyToBasicFields(): void
    {
        $translator = new OrderDddToFlatTranslator();
        $ddd = $this->makeDdd();
        $flat = new FlatOrder();

        $translator->applyTo($ddd, $flat);

        static::assertSame('D202601-001', $flat->orderNumber);
        static::assertSame('a_signer', $flat->status); // DRAFT collapses to a_signer
        static::assertSame('forfait', $flat->contractType);
        static::assertSame('10000', $flat->totalAmount);
    }

    public function testStatusMappingSigned(): void
    {
        $translator = new OrderDddToFlatTranslator();
        $ddd = $this->makeDdd(OrderStatus::SIGNED);
        $flat = new FlatOrder();

        $translator->applyTo($ddd, $flat);

        static::assertSame('signe', $flat->status);
    }

    public function testStatusMappingCompleted(): void
    {
        $translator = new OrderDddToFlatTranslator();
        $ddd = $this->makeDdd(OrderStatus::COMPLETED);
        $flat = new FlatOrder();

        $translator->applyTo($ddd, $flat);

        static::assertSame('termine', $flat->status);
    }

    public function testStatusDraftCollapsesToToSign(): void
    {
        $translator = new OrderDddToFlatTranslator();
        $ddd = $this->makeDdd(OrderStatus::DRAFT);
        $flat = new FlatOrder();

        $translator->applyTo($ddd, $flat);

        static::assertSame('a_signer', $flat->status);
    }

    public function testContractTypeMapping(): void
    {
        $translator = new OrderDddToFlatTranslator();
        $ddd = $this->makeDdd(null, ContractType::TIME_AND_MATERIAL);
        $flat = new FlatOrder();

        $translator->applyTo($ddd, $flat);

        static::assertSame('regie', $flat->contractType);
    }
}

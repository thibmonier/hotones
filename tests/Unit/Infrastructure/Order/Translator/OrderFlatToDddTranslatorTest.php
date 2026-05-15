<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Order\Translator;

use App\Domain\Order\ValueObject\ContractType;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Entity\Client as FlatClient;
use App\Entity\Order as FlatOrder;
use App\Entity\Project as FlatProject;
use App\Infrastructure\Order\Translator\OrderFlatToDddTranslator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

final class OrderFlatToDddTranslatorTest extends TestCase
{
    public function testTranslateWithProjectAndClient(): void
    {
        $flat = $this->makeFlat(id: 42, status: 'a_signer', contractType: 'forfait', amount: '10000.00');
        $client = $this->makeClient(7);
        $project = new FlatProject();
        $project->client = $client;
        $flat->project = $project;

        $ddd = new OrderFlatToDddTranslator()->translate($flat);

        static::assertSame(42, $ddd->getId()->toLegacyInt());
        static::assertSame(7, $ddd->getClientId()->toLegacyInt());
        static::assertSame(ContractType::FIXED_PRICE, $ddd->getContractType());
        static::assertSame(OrderStatus::TO_SIGN, $ddd->getStatus());
        static::assertSame(10_000.0, $ddd->getAmount()->getAmount());
    }

    public function testTranslateWithoutProjectFallsBackToPlaceholderClient(): void
    {
        $flat = $this->makeFlat(id: 1, status: 'a_signer', contractType: 'forfait', amount: '0.00');

        $ddd = new OrderFlatToDddTranslator()->translate($flat);

        static::assertSame(PHP_INT_MAX, $ddd->getClientId()->toLegacyInt());
    }

    public function testTranslateRegieContractType(): void
    {
        $flat = $this->makeFlat(id: 1, status: 'gagne', contractType: 'regie', amount: '5000.00');

        $ddd = new OrderFlatToDddTranslator()->translate($flat);

        static::assertSame(ContractType::TIME_AND_MATERIAL, $ddd->getContractType());
        static::assertSame(OrderStatus::WON, $ddd->getStatus());
    }

    public function testTranslateStatusMappings(): void
    {
        $cases = [
            'a_signer' => OrderStatus::TO_SIGN,
            'gagne' => OrderStatus::WON,
            'signe' => OrderStatus::SIGNED,
            'perdu' => OrderStatus::LOST,
            'termine' => OrderStatus::COMPLETED,
            'standby' => OrderStatus::STANDBY,
            'abandonne' => OrderStatus::ABANDONED,
        ];

        foreach ($cases as $flatStatus => $expected) {
            $entity = $this->makeFlat(id: 1, status: $flatStatus, contractType: 'forfait', amount: '0.00');
            $ddd = new OrderFlatToDddTranslator()->translate($entity);
            static::assertSame($expected, $ddd->getStatus(), sprintf('Status mapping for %s', $flatStatus));
        }
    }

    public function testTranslateUnknownStatusFallsBackToToSign(): void
    {
        $flat = $this->makeFlat(id: 1, status: 'inconnu', contractType: 'forfait', amount: '0.00');

        $ddd = new OrderFlatToDddTranslator()->translate($flat);

        static::assertSame(OrderStatus::TO_SIGN, $ddd->getStatus());
    }

    public function testTranslateUnknownContractTypeFallsBackToFixedPrice(): void
    {
        $flat = $this->makeFlat(id: 1, status: 'a_signer', contractType: 'unknown', amount: '0.00');

        $ddd = new OrderFlatToDddTranslator()->translate($flat);

        static::assertSame(ContractType::FIXED_PRICE, $ddd->getContractType());
    }

    public function testTranslateUnsavedOrderThrows(): void
    {
        $flat = new FlatOrder();
        $flat->orderNumber = 'D-001';
        $flat->status = 'a_signer';
        $flat->contractType = 'forfait';
        $flat->totalAmount = '0.00';

        $this->expectException(RuntimeException::class);
        new OrderFlatToDddTranslator()->translate($flat);
    }

    public function testTranslatePropagatesValidatedAtAsSignedAt(): void
    {
        $flat = $this->makeFlat(id: 1, status: 'signe', contractType: 'forfait', amount: '1000.00');
        $flat->validatedAt = new DateTimeImmutable('2026-03-15');

        $ddd = new OrderFlatToDddTranslator()->translate($flat);

        static::assertEquals(new DateTimeImmutable('2026-03-15'), $ddd->getSignedAt());
    }

    public function testTranslatePropagatesCreatedAtViaGetter(): void
    {
        $flat = $this->makeFlat(id: 1, status: 'a_signer', contractType: 'forfait', amount: '0.00');
        $flat->setCreatedAt(new DateTimeImmutable('2026-01-15'));

        $ddd = new OrderFlatToDddTranslator()->translate($flat);

        static::assertEquals(new DateTimeImmutable('2026-01-15'), $ddd->getCreatedAt());
    }

    public function testTranslateNullCreatedAtFallsBackToNow(): void
    {
        $flat = $this->makeFlat(id: 1, status: 'a_signer', contractType: 'forfait', amount: '0.00');
        // createdAt resté null — translator doit fallback sur new DateTimeImmutable()

        $ddd = new OrderFlatToDddTranslator()->translate($flat);

        static::assertNotNull($ddd->getCreatedAt());
    }

    private function makeFlat(int $id, string $status, string $contractType, string $amount): FlatOrder
    {
        $flat = new FlatOrder();
        new ReflectionProperty(FlatOrder::class, 'id')->setValue($flat, $id);
        $flat->orderNumber = 'D-'.$id;
        $flat->status = $status;
        $flat->contractType = $contractType;
        $flat->totalAmount = $amount;
        $flat->name = 'Test order';
        $flat->description = null;
        $flat->notes = null;
        $flat->validatedAt = null;

        return $flat;
    }

    private function makeClient(int $id): FlatClient
    {
        $client = new FlatClient();
        new ReflectionProperty(FlatClient::class, 'id')->setValue($client, $id);

        return $client;
    }
}

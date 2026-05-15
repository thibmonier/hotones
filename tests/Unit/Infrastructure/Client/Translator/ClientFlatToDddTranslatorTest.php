<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Client\Translator;

use App\Domain\Client\ValueObject\ServiceLevel;
use App\Entity\Client as FlatClient;
use App\Infrastructure\Client\Translator\ClientFlatToDddTranslator;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

final class ClientFlatToDddTranslatorTest extends TestCase
{
    private function makeFlatClient(int $id, string $name, ?string $level = 'standard'): FlatClient
    {
        $flat = new FlatClient();
        new ReflectionProperty(FlatClient::class, 'id')->setValue($flat, $id);
        $flat->name = $name;
        $flat->serviceLevel = $level;
        $flat->description = null;

        return $flat;
    }

    public function testTranslateBasicFields(): void
    {
        $translator = new ClientFlatToDddTranslator();
        $flat = $this->makeFlatClient(42, 'Acme Corp', 'standard');

        $ddd = $translator->translate($flat);

        static::assertSame('legacy:42', $ddd->getId()->getValue());
        static::assertSame('Acme Corp', $ddd->getName()->getValue());
        static::assertSame(ServiceLevel::PREMIUM, $ddd->getServiceLevel());
    }

    public function testServiceLevelMappingVip(): void
    {
        $translator = new ClientFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatClient(1, 'XY', 'vip'));
        static::assertSame(ServiceLevel::ENTERPRISE, $ddd->getServiceLevel());
    }

    public function testServiceLevelMappingPriority(): void
    {
        $translator = new ClientFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatClient(1, 'XY', 'priority'));
        static::assertSame(ServiceLevel::ENTERPRISE, $ddd->getServiceLevel());
    }

    public function testServiceLevelMappingLow(): void
    {
        $translator = new ClientFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatClient(1, 'XY', 'low'));
        static::assertSame(ServiceLevel::STANDARD, $ddd->getServiceLevel());
    }

    public function testServiceLevelMappingNullDefaults(): void
    {
        $translator = new ClientFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatClient(1, 'XY', null));
        static::assertSame(ServiceLevel::STANDARD, $ddd->getServiceLevel());
    }

    public function testTranslateUnsavedClientThrows(): void
    {
        $translator = new ClientFlatToDddTranslator();
        $flat = new FlatClient();
        $flat->name = 'Pending';

        $this->expectException(RuntimeException::class);
        $translator->translate($flat);
    }

    public function testReconstitutedClientHasNoDomainEvents(): void
    {
        $translator = new ClientFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatClient(1, 'XY'));

        // Reconstitute MUST NOT record events (entity exists in legacy store)
        static::assertSame([], $ddd->pullDomainEvents());
    }
}

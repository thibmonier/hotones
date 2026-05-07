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

        $this->assertSame('legacy:42', $ddd->getId()->getValue());
        $this->assertSame('Acme Corp', $ddd->getName()->getValue());
        $this->assertSame(ServiceLevel::PREMIUM, $ddd->getServiceLevel());
    }

    public function testServiceLevelMappingVip(): void
    {
        $translator = new ClientFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatClient(1, 'XY', 'vip'));
        $this->assertSame(ServiceLevel::ENTERPRISE, $ddd->getServiceLevel());
    }

    public function testServiceLevelMappingPriority(): void
    {
        $translator = new ClientFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatClient(1, 'XY', 'priority'));
        $this->assertSame(ServiceLevel::ENTERPRISE, $ddd->getServiceLevel());
    }

    public function testServiceLevelMappingLow(): void
    {
        $translator = new ClientFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatClient(1, 'XY', 'low'));
        $this->assertSame(ServiceLevel::STANDARD, $ddd->getServiceLevel());
    }

    public function testServiceLevelMappingNullDefaults(): void
    {
        $translator = new ClientFlatToDddTranslator();
        $ddd = $translator->translate($this->makeFlatClient(1, 'XY', null));
        $this->assertSame(ServiceLevel::STANDARD, $ddd->getServiceLevel());
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
        $this->assertSame([], $ddd->pullDomainEvents());
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Client\Translator;

use App\Domain\Client\Entity\Client as DddClient;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\ValueObject\CompanyName;
use App\Domain\Client\ValueObject\ServiceLevel;
use App\Entity\Client as FlatClient;
use App\Entity\Company;
use App\Infrastructure\Client\Translator\ClientDddToFlatTranslator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
final class ClientDddToFlatTranslatorTest extends TestCase
{
    private function makeCompany(): Company
    {
        $company = new Company();
        new ReflectionProperty(Company::class, 'id')->setValue($company, 1);

        return $company;
    }

    public function testApplyToBasicFields(): void
    {
        $translator = new ClientDddToFlatTranslator();
        $ddd = DddClient::create(
            ClientId::fromLegacyInt(42),
            CompanyName::fromString('Acme Corp'),
            ServiceLevel::PREMIUM,
        );
        $flat = new FlatClient();
        $company = $this->makeCompany();

        $translator->applyTo($ddd, $flat, $company);

        static::assertSame($company, $flat->company);
        static::assertSame('Acme Corp', $flat->name);
        static::assertSame('standard', $flat->serviceLevel);
    }

    public function testServiceLevelMappingEnterprise(): void
    {
        $translator = new ClientDddToFlatTranslator();
        $ddd = DddClient::create(ClientId::fromLegacyInt(1), CompanyName::fromString('XY'), ServiceLevel::ENTERPRISE);
        $flat = new FlatClient();

        $translator->applyTo($ddd, $flat, $this->makeCompany());

        static::assertSame('vip', $flat->serviceLevel);
    }

    public function testServiceLevelMappingStandard(): void
    {
        $translator = new ClientDddToFlatTranslator();
        $ddd = DddClient::create(ClientId::fromLegacyInt(1), CompanyName::fromString('XY'), ServiceLevel::STANDARD);
        $flat = new FlatClient();

        $translator->applyTo($ddd, $flat, $this->makeCompany());

        static::assertSame('low', $flat->serviceLevel);
    }

    public function testNotesArePropagated(): void
    {
        $translator = new ClientDddToFlatTranslator();
        $ddd = DddClient::create(ClientId::fromLegacyInt(1), CompanyName::fromString('XY'));
        $ddd->addNotes('Important notes');

        $flat = new FlatClient();
        $translator->applyTo($ddd, $flat, $this->makeCompany());

        static::assertSame('Important notes', $flat->description);
    }
}

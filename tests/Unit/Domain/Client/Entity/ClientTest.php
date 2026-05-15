<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Client\Entity;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\Event\ClientCreatedEvent;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\ValueObject\CompanyName;
use App\Domain\Client\ValueObject\ServiceLevel;
use App\Domain\Shared\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    private function makeClient(): Client
    {
        return Client::create(ClientId::generate(), CompanyName::fromString('Acme Corp'));
    }

    public function testCreateInitializesDefaults(): void
    {
        $client = $this->makeClient();

        static::assertSame('Acme Corp', $client->getName()->getValue());
        static::assertSame(ServiceLevel::STANDARD, $client->getServiceLevel());
        static::assertTrue($client->isActive());
        static::assertNull($client->getEmail());
        static::assertNull($client->getUpdatedAt());
        static::assertNotNull($client->getCreatedAt());
    }

    public function testCreateRecordsClientCreatedEvent(): void
    {
        $client = $this->makeClient();
        $events = $client->pullDomainEvents();

        static::assertCount(1, $events);
        static::assertInstanceOf(ClientCreatedEvent::class, $events[0]);
        static::assertSame([], $client->pullDomainEvents());
    }

    public function testCreateWithExplicitServiceLevel(): void
    {
        $client = Client::create(
            ClientId::generate(),
            CompanyName::fromString('Premium Corp'),
            ServiceLevel::ENTERPRISE,
        );

        static::assertSame(ServiceLevel::ENTERPRISE, $client->getServiceLevel());
    }

    public function testUpdateContactInfo(): void
    {
        $client = $this->makeClient();
        $client->updateContactInfo(
            Email::fromString('contact@acme.com'),
            '+33123456789',
            '1 rue de la Paix',
            'Paris',
            '75001',
            'FR',
        );

        static::assertSame('contact@acme.com', $client->getEmail()->getValue());
        static::assertSame('+33123456789', $client->getPhone());
        static::assertSame('Paris', $client->getCity());
        static::assertSame('75001', $client->getPostalCode());
        static::assertSame('FR', $client->getCountry());
        static::assertNotNull($client->getUpdatedAt());
    }

    public function testUpdateServiceLevel(): void
    {
        $client = $this->makeClient();
        static::assertNull($client->getUpdatedAt());

        $client->updateServiceLevel(ServiceLevel::PREMIUM);
        static::assertSame(ServiceLevel::PREMIUM, $client->getServiceLevel());
        static::assertNotNull($client->getUpdatedAt());
    }

    public function testUpdateServiceLevelNoOpIfSame(): void
    {
        $client = $this->makeClient();
        $client->updateServiceLevel(ServiceLevel::STANDARD); // same as default

        static::assertNull($client->getUpdatedAt());
    }

    public function testRename(): void
    {
        $client = $this->makeClient();
        $client->rename(CompanyName::fromString('New Name'));

        static::assertSame('New Name', $client->getName()->getValue());
        static::assertNotNull($client->getUpdatedAt());
    }

    public function testRenameNoOpIfSame(): void
    {
        $client = $this->makeClient();
        $client->rename(CompanyName::fromString('Acme Corp'));

        static::assertNull($client->getUpdatedAt());
    }

    public function testActivateDeactivate(): void
    {
        $client = $this->makeClient();
        static::assertTrue($client->isActive());

        $client->deactivate();
        static::assertFalse($client->isActive());
        static::assertNotNull($client->getUpdatedAt());

        $client->activate();
        static::assertTrue($client->isActive());
    }

    public function testDeactivateNoOpIfAlreadyInactive(): void
    {
        $client = $this->makeClient();
        $client->deactivate();
        $firstUpdate = $client->getUpdatedAt();

        $client->deactivate();
        static::assertSame($firstUpdate, $client->getUpdatedAt());
    }

    public function testUpdateVatNumber(): void
    {
        $client = $this->makeClient();
        $client->updateVatNumber('FR12345678901');

        static::assertSame('FR12345678901', $client->getVatNumber());
        static::assertNotNull($client->getUpdatedAt());
    }

    public function testAddNotes(): void
    {
        $client = $this->makeClient();
        $client->addNotes('Important client');

        static::assertSame('Important client', $client->getNotes());
        static::assertNotNull($client->getUpdatedAt());
    }
}

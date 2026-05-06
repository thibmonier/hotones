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
        return Client::create(
            ClientId::generate(),
            CompanyName::fromString('Acme Corp'),
        );
    }

    public function testCreateInitializesDefaults(): void
    {
        $client = $this->makeClient();

        $this->assertSame('Acme Corp', $client->getName()->getValue());
        $this->assertSame(ServiceLevel::STANDARD, $client->getServiceLevel());
        $this->assertTrue($client->isActive());
        $this->assertNull($client->getEmail());
        $this->assertNull($client->getUpdatedAt());
        $this->assertNotNull($client->getCreatedAt());
    }

    public function testCreateRecordsClientCreatedEvent(): void
    {
        $client = $this->makeClient();
        $events = $client->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ClientCreatedEvent::class, $events[0]);
        $this->assertSame([], $client->pullDomainEvents());
    }

    public function testCreateWithExplicitServiceLevel(): void
    {
        $client = Client::create(
            ClientId::generate(),
            CompanyName::fromString('Premium Corp'),
            ServiceLevel::ENTERPRISE,
        );

        $this->assertSame(ServiceLevel::ENTERPRISE, $client->getServiceLevel());
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

        $this->assertSame('contact@acme.com', $client->getEmail()->getValue());
        $this->assertSame('+33123456789', $client->getPhone());
        $this->assertSame('Paris', $client->getCity());
        $this->assertSame('75001', $client->getPostalCode());
        $this->assertSame('FR', $client->getCountry());
        $this->assertNotNull($client->getUpdatedAt());
    }

    public function testUpdateServiceLevel(): void
    {
        $client = $this->makeClient();
        $this->assertNull($client->getUpdatedAt());

        $client->updateServiceLevel(ServiceLevel::PREMIUM);
        $this->assertSame(ServiceLevel::PREMIUM, $client->getServiceLevel());
        $this->assertNotNull($client->getUpdatedAt());
    }

    public function testUpdateServiceLevelNoOpIfSame(): void
    {
        $client = $this->makeClient();
        $client->updateServiceLevel(ServiceLevel::STANDARD); // same as default

        $this->assertNull($client->getUpdatedAt());
    }

    public function testRename(): void
    {
        $client = $this->makeClient();
        $client->rename(CompanyName::fromString('New Name'));

        $this->assertSame('New Name', $client->getName()->getValue());
        $this->assertNotNull($client->getUpdatedAt());
    }

    public function testRenameNoOpIfSame(): void
    {
        $client = $this->makeClient();
        $client->rename(CompanyName::fromString('Acme Corp'));

        $this->assertNull($client->getUpdatedAt());
    }

    public function testActivateDeactivate(): void
    {
        $client = $this->makeClient();
        $this->assertTrue($client->isActive());

        $client->deactivate();
        $this->assertFalse($client->isActive());
        $this->assertNotNull($client->getUpdatedAt());

        $client->activate();
        $this->assertTrue($client->isActive());
    }

    public function testDeactivateNoOpIfAlreadyInactive(): void
    {
        $client = $this->makeClient();
        $client->deactivate();
        $firstUpdate = $client->getUpdatedAt();

        $client->deactivate();
        $this->assertSame($firstUpdate, $client->getUpdatedAt());
    }

    public function testUpdateVatNumber(): void
    {
        $client = $this->makeClient();
        $client->updateVatNumber('FR12345678901');

        $this->assertSame('FR12345678901', $client->getVatNumber());
        $this->assertNotNull($client->getUpdatedAt());
    }

    public function testAddNotes(): void
    {
        $client = $this->makeClient();
        $client->addNotes('Important client');

        $this->assertSame('Important client', $client->getNotes());
        $this->assertNotNull($client->getUpdatedAt());
    }
}

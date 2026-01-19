<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Client\Entity;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\Event\ClientCreatedEvent;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\ValueObject\CompanyName;
use App\Domain\Client\ValueObject\ServiceLevel;
use App\Domain\Shared\ValueObject\Email;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Client Domain Entity.
 *
 * Tests cover:
 * - Factory method creation with domain events
 * - Business methods (updateContactInfo, updateServiceLevel, rename, etc.)
 * - Idempotency checks for state changes
 * - Domain event recording
 */
final class ClientTest extends TestCase
{
    // ========================================================================
    // Factory Method Tests
    // ========================================================================

    #[Test]
    public function itCreatesAClientWithRequiredFields(): void
    {
        // Given
        $id           = ClientId::generate();
        $name         = CompanyName::fromString('Acme Corporation');
        $serviceLevel = ServiceLevel::STANDARD;

        // When
        $client = Client::create($id, $name, $serviceLevel);

        // Then
        self::assertTrue($id->equals($client->getId()));
        self::assertTrue($name->equals($client->getName()));
        self::assertSame($serviceLevel, $client->getServiceLevel());
        self::assertTrue($client->isActive());
        self::assertNull($client->getEmail());
        self::assertNull($client->getPhone());
        self::assertNull($client->getAddress());
        self::assertNull($client->getCity());
        self::assertNull($client->getPostalCode());
        self::assertNull($client->getCountry());
        self::assertNull($client->getVatNumber());
        self::assertNull($client->getNotes());
        self::assertInstanceOf(DateTimeImmutable::class, $client->getCreatedAt());
        self::assertNull($client->getUpdatedAt());
    }

    #[Test]
    public function itCreatesAClientWithDefaultServiceLevel(): void
    {
        // Given
        $id   = ClientId::generate();
        $name = CompanyName::fromString('Default Level Corp');

        // When
        $client = Client::create($id, $name);

        // Then
        self::assertSame(ServiceLevel::STANDARD, $client->getServiceLevel());
    }

    #[Test]
    public function itCreatesAClientWithPremiumServiceLevel(): void
    {
        // Given
        $id   = ClientId::generate();
        $name = CompanyName::fromString('Premium Corp');

        // When
        $client = Client::create($id, $name, ServiceLevel::PREMIUM);

        // Then
        self::assertSame(ServiceLevel::PREMIUM, $client->getServiceLevel());
    }

    #[Test]
    public function itCreatesAClientWithEnterpriseServiceLevel(): void
    {
        // Given
        $id   = ClientId::generate();
        $name = CompanyName::fromString('Enterprise Corp');

        // When
        $client = Client::create($id, $name, ServiceLevel::ENTERPRISE);

        // Then
        self::assertSame(ServiceLevel::ENTERPRISE, $client->getServiceLevel());
    }

    // ========================================================================
    // Domain Events Tests
    // ========================================================================

    #[Test]
    public function itRecordsClientCreatedEventOnCreation(): void
    {
        // Given
        $id   = ClientId::generate();
        $name = CompanyName::fromString('Event Test Corp');

        // When
        $client = Client::create($id, $name);
        $events = $client->pullDomainEvents();

        // Then
        self::assertCount(1, $events);
        self::assertInstanceOf(ClientCreatedEvent::class, $events[0]);
        self::assertTrue($id->equals($events[0]->getClientId()));
        self::assertSame('Event Test Corp', $events[0]->getCompanyName());
    }

    #[Test]
    public function pullDomainEventsClearsTheEventsQueue(): void
    {
        // Given
        $client = $this->createClient();

        // When
        $firstPull  = $client->pullDomainEvents();
        $secondPull = $client->pullDomainEvents();

        // Then
        self::assertCount(1, $firstPull);
        self::assertCount(0, $secondPull);
    }

    // ========================================================================
    // Update Contact Info Tests
    // ========================================================================

    #[Test]
    public function itUpdatesContactInfoWithAllFields(): void
    {
        // Given
        $client = $this->createClient();
        $client->pullDomainEvents(); // Clear creation event
        $email      = Email::fromString('contact@acme.com');
        $phone      = '+33 1 23 45 67 89';
        $address    = '123 Main Street';
        $city       = 'Paris';
        $postalCode = '75001';
        $country    = 'France';

        // When
        $client->updateContactInfo($email, $phone, $address, $city, $postalCode, $country);

        // Then
        self::assertTrue($email->equals($client->getEmail()));
        self::assertSame($phone, $client->getPhone());
        self::assertSame($address, $client->getAddress());
        self::assertSame($city, $client->getCity());
        self::assertSame($postalCode, $client->getPostalCode());
        self::assertSame($country, $client->getCountry());
        self::assertInstanceOf(DateTimeImmutable::class, $client->getUpdatedAt());
    }

    #[Test]
    public function itUpdatesContactInfoWithPartialFields(): void
    {
        // Given
        $client = $this->createClient();
        $email  = Email::fromString('partial@test.com');

        // When
        $client->updateContactInfo($email);

        // Then
        self::assertTrue($email->equals($client->getEmail()));
        self::assertNull($client->getPhone());
        self::assertNull($client->getAddress());
        self::assertNull($client->getCity());
        self::assertNull($client->getPostalCode());
        self::assertNull($client->getCountry());
    }

    #[Test]
    public function itUpdatesContactInfoWithNullEmail(): void
    {
        // Given
        $client = $this->createClient();
        $phone  = '+33 6 12 34 56 78';

        // When
        $client->updateContactInfo(null, $phone);

        // Then
        self::assertNull($client->getEmail());
        self::assertSame($phone, $client->getPhone());
    }

    // ========================================================================
    // Update Service Level Tests
    // ========================================================================

    #[Test]
    public function itUpdatesServiceLevel(): void
    {
        // Given
        $client = $this->createClient();
        $client->pullDomainEvents();
        $originalUpdatedAt = $client->getUpdatedAt();

        // When
        $client->updateServiceLevel(ServiceLevel::PREMIUM);

        // Then
        self::assertSame(ServiceLevel::PREMIUM, $client->getServiceLevel());
        self::assertNotNull($client->getUpdatedAt());
        self::assertNotSame($originalUpdatedAt, $client->getUpdatedAt());
    }

    #[Test]
    public function itDoesNotUpdateWhenServiceLevelIsSame(): void
    {
        // Given
        $client = $this->createClient();
        $client->updateServiceLevel(ServiceLevel::PREMIUM);
        $updatedAtAfterFirstChange = $client->getUpdatedAt();

        // Small delay to ensure time difference if updated
        usleep(1000);

        // When
        $client->updateServiceLevel(ServiceLevel::PREMIUM);

        // Then (idempotency check - should not update)
        self::assertSame($updatedAtAfterFirstChange, $client->getUpdatedAt());
    }

    #[Test]
    public function itUpgradesFromStandardToEnterprise(): void
    {
        // Given
        $client = Client::create(
            ClientId::generate(),
            CompanyName::fromString('Upgrade Corp'),
            ServiceLevel::STANDARD,
        );

        // When
        $client->updateServiceLevel(ServiceLevel::ENTERPRISE);

        // Then
        self::assertSame(ServiceLevel::ENTERPRISE, $client->getServiceLevel());
    }

    // ========================================================================
    // Rename Tests
    // ========================================================================

    #[Test]
    public function itRenamesTheClient(): void
    {
        // Given
        $client = $this->createClient();
        $client->pullDomainEvents();
        $newName = CompanyName::fromString('New Company Name');

        // When
        $client->rename($newName);

        // Then
        self::assertTrue($newName->equals($client->getName()));
        self::assertInstanceOf(DateTimeImmutable::class, $client->getUpdatedAt());
    }

    #[Test]
    public function itDoesNotUpdateWhenNameIsSame(): void
    {
        // Given
        $originalName = CompanyName::fromString('Same Name Corp');
        $client       = Client::create(ClientId::generate(), $originalName);
        $client->rename(CompanyName::fromString('Different Name'));
        $updatedAtAfterFirstRename = $client->getUpdatedAt();

        usleep(1000);

        // When - rename back to different, then same
        $client->rename(CompanyName::fromString('Different Name'));

        // Then (idempotency check)
        self::assertSame($updatedAtAfterFirstRename, $client->getUpdatedAt());
    }

    // ========================================================================
    // Activate/Deactivate Tests
    // ========================================================================

    #[Test]
    public function itDeactivatesAnActiveClient(): void
    {
        // Given
        $client = $this->createClient();
        self::assertTrue($client->isActive());

        // When
        $client->deactivate();

        // Then
        self::assertFalse($client->isActive());
        self::assertInstanceOf(DateTimeImmutable::class, $client->getUpdatedAt());
    }

    #[Test]
    public function itActivatesAnInactiveClient(): void
    {
        // Given
        $client = $this->createClient();
        $client->deactivate();
        self::assertFalse($client->isActive());

        // When
        $client->activate();

        // Then
        self::assertTrue($client->isActive());
    }

    #[Test]
    public function itDoesNotUpdateWhenAlreadyActive(): void
    {
        // Given
        $client = $this->createClient();
        self::assertTrue($client->isActive());
        $client->deactivate();
        $client->activate();
        $updatedAtAfterActivation = $client->getUpdatedAt();

        usleep(1000);

        // When
        $client->activate();

        // Then (idempotency check)
        self::assertSame($updatedAtAfterActivation, $client->getUpdatedAt());
    }

    #[Test]
    public function itDoesNotUpdateWhenAlreadyInactive(): void
    {
        // Given
        $client = $this->createClient();
        $client->deactivate();
        $updatedAtAfterDeactivation = $client->getUpdatedAt();

        usleep(1000);

        // When
        $client->deactivate();

        // Then (idempotency check)
        self::assertSame($updatedAtAfterDeactivation, $client->getUpdatedAt());
    }

    // ========================================================================
    // VAT Number Tests
    // ========================================================================

    #[Test]
    public function itUpdatesVatNumber(): void
    {
        // Given
        $client    = $this->createClient();
        $vatNumber = 'FR12345678901';

        // When
        $client->updateVatNumber($vatNumber);

        // Then
        self::assertSame($vatNumber, $client->getVatNumber());
        self::assertInstanceOf(DateTimeImmutable::class, $client->getUpdatedAt());
    }

    #[Test]
    public function itClearsVatNumberWithNull(): void
    {
        // Given
        $client = $this->createClient();
        $client->updateVatNumber('FR12345678901');
        self::assertNotNull($client->getVatNumber());

        // When
        $client->updateVatNumber(null);

        // Then
        self::assertNull($client->getVatNumber());
    }

    // ========================================================================
    // Notes Tests
    // ========================================================================

    #[Test]
    public function itAddsNotes(): void
    {
        // Given
        $client = $this->createClient();
        $notes  = 'Important client - handle with care';

        // When
        $client->addNotes($notes);

        // Then
        self::assertSame($notes, $client->getNotes());
        self::assertInstanceOf(DateTimeImmutable::class, $client->getUpdatedAt());
    }

    #[Test]
    public function itClearsNotesWithNull(): void
    {
        // Given
        $client = $this->createClient();
        $client->addNotes('Some notes');
        self::assertNotNull($client->getNotes());

        // When
        $client->addNotes(null);

        // Then
        self::assertNull($client->getNotes());
    }

    #[Test]
    public function itReplacesExistingNotes(): void
    {
        // Given
        $client = $this->createClient();
        $client->addNotes('Old notes');

        // When
        $client->addNotes('New notes');

        // Then
        self::assertSame('New notes', $client->getNotes());
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    private function createClient(): Client
    {
        return Client::create(
            ClientId::generate(),
            CompanyName::fromString('Test Company'),
            ServiceLevel::STANDARD,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Client;
use App\Entity\ClientContact;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testClientCreationAndProperties(): void
    {
        $client = new Client();

        // Test initial state
        static::assertNull($client->getId());
        static::assertEmpty($client->getName());
        static::assertNull($client->getLogoPath());
        static::assertNull($client->getWebsite());
        static::assertNull($client->getDescription());
        static::assertNull($client->getServiceLevel());
        static::assertSame('auto', $client->getServiceLevelMode());
        static::assertCount(0, $client->getContacts());
    }

    public function testClientPropertiesSettersAndGetters(): void
    {
        $client = new Client();

        $client->setName('Acme Corporation');
        $client->setLogoPath('/path/to/logo.png');
        $client->setWebsite('https://acme.com');
        $client->setDescription('A test company');
        $client->setServiceLevel('vip');
        $client->setServiceLevelMode('manual');

        static::assertSame('Acme Corporation', $client->getName());
        static::assertSame('/path/to/logo.png', $client->getLogoPath());
        static::assertSame('https://acme.com', $client->getWebsite());
        static::assertSame('A test company', $client->getDescription());
        static::assertSame('vip', $client->getServiceLevel());
        static::assertSame('manual', $client->getServiceLevelMode());
    }

    public function testClientContactsManagement(): void
    {
        $client = new Client();
        $contact1 = new ClientContact();
        $contact2 = new ClientContact();

        // Test adding contacts
        $client->addContact($contact1);
        $client->addContact($contact2);

        static::assertCount(2, $client->getContacts());
        static::assertTrue($client->getContacts()->contains($contact1));
        static::assertTrue($client->getContacts()->contains($contact2));

        // Test removing contact
        $client->removeContact($contact1);
        static::assertCount(1, $client->getContacts());
        static::assertFalse($client->getContacts()->contains($contact1));
        static::assertTrue($client->getContacts()->contains($contact2));
    }

    public function testClientStringRepresentation(): void
    {
        $client = new Client();
        $client->setName('Test Company');

        static::assertSame('Test Company', (string) $client);
    }

    public function testClientServiceLevelValidation(): void
    {
        $client = new Client();

        // Test valid service levels
        $validLevels = ['vip', 'priority', 'standard', 'low'];
        foreach ($validLevels as $level) {
            $client->setServiceLevel($level);
            static::assertEquals($level, $client->getServiceLevel());
        }

        // Test null service level
        $client->setServiceLevel(null);
        static::assertNull($client->getServiceLevel());
    }
}

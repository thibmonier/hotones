<?php

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
        $this->assertNull($client->getId());
        $this->assertEmpty($client->getName());
        $this->assertNull($client->getLogoPath());
        $this->assertNull($client->getWebsite());
        $this->assertNull($client->getDescription());
        $this->assertNull($client->getServiceLevel());
        $this->assertEquals('auto', $client->getServiceLevelMode());
        $this->assertCount(0, $client->getContacts());
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

        $this->assertEquals('Acme Corporation', $client->getName());
        $this->assertEquals('/path/to/logo.png', $client->getLogoPath());
        $this->assertEquals('https://acme.com', $client->getWebsite());
        $this->assertEquals('A test company', $client->getDescription());
        $this->assertEquals('vip', $client->getServiceLevel());
        $this->assertEquals('manual', $client->getServiceLevelMode());
    }

    public function testClientContactsManagement(): void
    {
        $client   = new Client();
        $contact1 = new ClientContact();
        $contact2 = new ClientContact();

        // Test adding contacts
        $client->addContact($contact1);
        $client->addContact($contact2);

        $this->assertCount(2, $client->getContacts());
        $this->assertTrue($client->getContacts()->contains($contact1));
        $this->assertTrue($client->getContacts()->contains($contact2));

        // Test removing contact
        $client->removeContact($contact1);
        $this->assertCount(1, $client->getContacts());
        $this->assertFalse($client->getContacts()->contains($contact1));
        $this->assertTrue($client->getContacts()->contains($contact2));
    }

    public function testClientStringRepresentation(): void
    {
        $client = new Client();
        $client->setName('Test Company');

        $this->assertEquals('Test Company', (string) $client);
    }

    public function testClientServiceLevelValidation(): void
    {
        $client = new Client();

        // Test valid service levels
        $validLevels = ['vip', 'priority', 'standard', 'low'];
        foreach ($validLevels as $level) {
            $client->setServiceLevel($level);
            $this->assertEquals($level, $client->getServiceLevel());
        }

        // Test null service level
        $client->setServiceLevel(null);
        $this->assertNull($client->getServiceLevel());
    }
}

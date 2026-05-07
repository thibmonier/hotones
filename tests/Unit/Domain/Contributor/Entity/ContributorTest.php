<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Contributor\Entity;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\Entity\Contributor;
use App\Domain\Contributor\Event\ContributorCreatedEvent;
use App\Domain\Contributor\ValueObject\ContractStatus;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Contributor\ValueObject\PersonName;
use PHPUnit\Framework\TestCase;

final class ContributorTest extends TestCase
{
    private function makeContributor(): Contributor
    {
        return Contributor::create(
            ContributorId::fromLegacyInt(42),
            CompanyId::fromLegacyInt(1),
            PersonName::fromParts('Jean', 'Dupont'),
        );
    }

    public function testCreateInitializes(): void
    {
        $c = $this->makeContributor();

        $this->assertSame(42, $c->getId()->toLegacyInt());
        $this->assertSame('Jean Dupont', $c->getName()->getFullName());
        $this->assertSame(ContractStatus::ACTIVE, $c->getStatus());
        $this->assertTrue($c->isActive());
        $this->assertNull($c->getEmail());
        $this->assertNull($c->getManagerId());
        $this->assertNull($c->getUpdatedAt());
    }

    public function testCreateRecordsEvent(): void
    {
        $c = $this->makeContributor();
        $events = $c->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ContributorCreatedEvent::class, $events[0]);
    }

    public function testRename(): void
    {
        $c = $this->makeContributor();
        $c->pullDomainEvents();

        $c->rename(PersonName::fromParts('Marie', 'Dupont'));

        $this->assertSame('Marie Dupont', $c->getName()->getFullName());
        $this->assertNotNull($c->getUpdatedAt());
    }

    public function testRenameSameNameNoOp(): void
    {
        $c = $this->makeContributor();
        $c->pullDomainEvents();

        $c->rename(PersonName::fromParts('Jean', 'Dupont'));

        $this->assertNull($c->getUpdatedAt());
    }

    public function testSetEmail(): void
    {
        $c = $this->makeContributor();
        $c->setEmail('jean@example.com');

        $this->assertSame('jean@example.com', $c->getEmail());
        $this->assertNotNull($c->getUpdatedAt());
    }

    public function testDeactivate(): void
    {
        $c = $this->makeContributor();
        $c->deactivate();

        $this->assertSame(ContractStatus::INACTIVE, $c->getStatus());
        $this->assertFalse($c->isActive());
    }

    public function testReactivate(): void
    {
        $c = $this->makeContributor();
        $c->deactivate();
        $c->reactivate();

        $this->assertTrue($c->isActive());
    }

    public function testSetManager(): void
    {
        $c = $this->makeContributor();
        $managerId = ContributorId::fromLegacyInt(7);
        $c->setManager($managerId);

        $this->assertSame(7, $c->getManagerId()->toLegacyInt());
    }

    public function testReconstituteNoEvents(): void
    {
        $c = Contributor::reconstitute(
            ContributorId::fromLegacyInt(7),
            CompanyId::fromLegacyInt(1),
            PersonName::fromParts('Jean', 'Dupont'),
            ContractStatus::INACTIVE,
        );

        $this->assertCount(0, $c->pullDomainEvents());
        $this->assertSame(ContractStatus::INACTIVE, $c->getStatus());
    }
}

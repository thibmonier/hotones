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

        static::assertSame(42, $c->getId()->toLegacyInt());
        static::assertSame('Jean Dupont', $c->getName()->getFullName());
        static::assertSame(ContractStatus::ACTIVE, $c->getStatus());
        static::assertTrue($c->isActive());
        static::assertNull($c->getEmail());
        static::assertNull($c->getManagerId());
        static::assertNull($c->getUpdatedAt());
    }

    public function testCreateRecordsEvent(): void
    {
        $c = $this->makeContributor();
        $events = $c->pullDomainEvents();

        static::assertCount(1, $events);
        static::assertInstanceOf(ContributorCreatedEvent::class, $events[0]);
    }

    public function testRename(): void
    {
        $c = $this->makeContributor();
        $c->pullDomainEvents();

        $c->rename(PersonName::fromParts('Marie', 'Dupont'));

        static::assertSame('Marie Dupont', $c->getName()->getFullName());
        static::assertNotNull($c->getUpdatedAt());
    }

    public function testRenameSameNameNoOp(): void
    {
        $c = $this->makeContributor();
        $c->pullDomainEvents();

        $c->rename(PersonName::fromParts('Jean', 'Dupont'));

        static::assertNull($c->getUpdatedAt());
    }

    public function testSetEmail(): void
    {
        $c = $this->makeContributor();
        $c->setEmail('jean@example.com');

        static::assertSame('jean@example.com', $c->getEmail());
        static::assertNotNull($c->getUpdatedAt());
    }

    public function testDeactivate(): void
    {
        $c = $this->makeContributor();
        $c->deactivate();

        static::assertSame(ContractStatus::INACTIVE, $c->getStatus());
        static::assertFalse($c->isActive());
    }

    public function testReactivate(): void
    {
        $c = $this->makeContributor();
        $c->deactivate();
        $c->reactivate();

        static::assertTrue($c->isActive());
    }

    public function testSetManager(): void
    {
        $c = $this->makeContributor();
        $managerId = ContributorId::fromLegacyInt(7);
        $c->setManager($managerId);

        static::assertSame(7, $c->getManagerId()->toLegacyInt());
    }

    public function testReconstituteNoEvents(): void
    {
        $c = Contributor::reconstitute(
            ContributorId::fromLegacyInt(7),
            CompanyId::fromLegacyInt(1),
            PersonName::fromParts('Jean', 'Dupont'),
            ContractStatus::INACTIVE,
        );

        static::assertCount(0, $c->pullDomainEvents());
        static::assertSame(ContractStatus::INACTIVE, $c->getStatus());
    }
}

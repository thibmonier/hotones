<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Contributor\Event;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\Event\ContributorCreatedEvent;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Contributor\ValueObject\PersonName;
use App\Domain\Shared\Interface\DomainEventInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ContributorCreatedEventTest extends TestCase
{
    public function testCreateBuildsEventWithFields(): void
    {
        $contributorId = ContributorId::generate();
        $companyId = CompanyId::generate();
        $name = PersonName::fromParts('Jean', 'Dupont');

        $event = ContributorCreatedEvent::create($contributorId, $companyId, $name);

        static::assertInstanceOf(DomainEventInterface::class, $event);
        static::assertSame($contributorId, $event->contributorId);
        static::assertSame($companyId, $event->companyId);
        static::assertSame($name, $event->name);
    }

    public function testGetOccurredOnReturnsConstructTime(): void
    {
        $before = new DateTimeImmutable();

        $event = ContributorCreatedEvent::create(
            ContributorId::generate(),
            CompanyId::generate(),
            PersonName::fromParts('A', 'B'),
        );

        $after = new DateTimeImmutable();

        static::assertGreaterThanOrEqual($before, $event->getOccurredOn());
        static::assertLessThanOrEqual($after, $event->getOccurredOn());
    }

    public function testGetAggregateIdReturnsContributorId(): void
    {
        $contributorId = ContributorId::generate();

        $event = ContributorCreatedEvent::create(
            $contributorId,
            CompanyId::generate(),
            PersonName::fromParts('A', 'B'),
        );

        static::assertSame($contributorId->getValue(), $event->getAggregateId());
    }
}

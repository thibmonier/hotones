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

        self::assertInstanceOf(DomainEventInterface::class, $event);
        self::assertSame($contributorId, $event->contributorId);
        self::assertSame($companyId, $event->companyId);
        self::assertSame($name, $event->name);
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

        self::assertGreaterThanOrEqual($before, $event->getOccurredOn());
        self::assertLessThanOrEqual($after, $event->getOccurredOn());
    }

    public function testGetAggregateIdReturnsContributorId(): void
    {
        $contributorId = ContributorId::generate();

        $event = ContributorCreatedEvent::create(
            $contributorId,
            CompanyId::generate(),
            PersonName::fromParts('A', 'B'),
        );

        self::assertSame($contributorId->getValue(), $event->getAggregateId());
    }
}

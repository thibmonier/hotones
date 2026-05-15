<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Contributor\Query\ListActiveContributors;

use App\Application\Contributor\Query\ListActiveContributors\ListActiveContributorsHandler;
use App\Application\Contributor\Query\ListActiveContributors\ListActiveContributorsQuery;
use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\Entity\Contributor;
use App\Domain\Contributor\Repository\ContributorRepositoryInterface;
use App\Domain\Contributor\ValueObject\ContractStatus;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Contributor\ValueObject\PersonName;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Sprint-018 Phase 3 strangler fig Contributor BC — coverage Unit du
 * Handler `ListActiveContributors`.
 */
#[AllowMockObjectsWithoutExpectations]
final class ListActiveContributorsHandlerTest extends TestCase
{
    public function testReturnsEmptyArrayWhenNoActiveContributors(): void
    {
        $repository = $this->createMock(ContributorRepositoryInterface::class);
        $repository->method('findActive')->willReturn([]);

        $handler = new ListActiveContributorsHandler($repository);

        $result = $handler(new ListActiveContributorsQuery());

        static::assertSame([], $result);
    }

    public function testMapsAggregatesToDtos(): void
    {
        $contributor1 = Contributor::reconstitute(
            ContributorId::fromLegacyInt(7),
            CompanyId::fromLegacyInt(1),
            PersonName::fromParts('Alice', 'Wonder'),
            ContractStatus::ACTIVE,
            ['email' => 'alice@example.org'],
        );
        $contributor2 = Contributor::reconstitute(
            ContributorId::fromLegacyInt(11),
            CompanyId::fromLegacyInt(1),
            PersonName::fromParts('Bob', 'Builder'),
            ContractStatus::ACTIVE,
            ['createdAt' => new DateTimeImmutable('2025-01-15T10:00:00+00:00')],
        );

        $repository = $this->createMock(ContributorRepositoryInterface::class);
        $repository->method('findActive')->willReturn([$contributor1, $contributor2]);

        $handler = new ListActiveContributorsHandler($repository);

        $result = $handler(new ListActiveContributorsQuery());

        static::assertCount(2, $result);
        static::assertSame(7, $result[0]->id);
        static::assertSame('Alice', $result[0]->firstName);
        static::assertSame('Alice Wonder', $result[0]->fullName);
        static::assertSame('alice@example.org', $result[0]->email);
        static::assertSame('active', $result[0]->status);
        static::assertSame(1, $result[0]->companyId);
        static::assertNull($result[0]->managerId);

        static::assertSame(11, $result[1]->id);
        static::assertSame('Bob Builder', $result[1]->fullName);
        static::assertNull($result[1]->email);
        static::assertSame('2025-01-15T10:00:00+00:00', $result[1]->createdAt);
    }

    public function testDtoToArrayShape(): void
    {
        $contributor = Contributor::reconstitute(
            ContributorId::fromLegacyInt(42),
            CompanyId::fromLegacyInt(2),
            PersonName::fromParts('Carol', 'Smith'),
            ContractStatus::ACTIVE,
            [
                'email' => 'carol@example.org',
                'managerId' => ContributorId::fromLegacyInt(7),
                'createdAt' => new DateTimeImmutable('2025-02-01T09:00:00+00:00'),
            ],
        );

        $repository = $this->createMock(ContributorRepositoryInterface::class);
        $repository->method('findActive')->willReturn([$contributor]);

        $handler = new ListActiveContributorsHandler($repository);

        $result = $handler(new ListActiveContributorsQuery());
        $array = $result[0]->toArray();

        static::assertSame([
            'id' => 42,
            'companyId' => 2,
            'firstName' => 'Carol',
            'lastName' => 'Smith',
            'fullName' => 'Carol Smith',
            'email' => 'carol@example.org',
            'status' => 'active',
            'managerId' => 7,
            'createdAt' => '2025-02-01T09:00:00+00:00',
        ], $array);
    }

    public function testHandlerDelegatesToRepositoryFindActive(): void
    {
        $repository = $this->createMock(ContributorRepositoryInterface::class);
        $repository->expects(self::once())->method('findActive')->willReturn([]);

        $handler = new ListActiveContributorsHandler($repository);
        $handler(new ListActiveContributorsQuery());
    }
}

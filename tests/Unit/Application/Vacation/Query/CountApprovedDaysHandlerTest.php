<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Vacation\Query;

use App\Application\Vacation\Query\CountApprovedDays\CountApprovedDaysHandler;
use App\Application\Vacation\Query\CountApprovedDays\CountApprovedDaysQuery;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CountApprovedDaysHandler (TEST-005, sprint-003).
 *
 * Pure thin-shim test : assert the query parameters reach the repository
 * verbatim and the float total is forwarded back to the caller.
 */
final class CountApprovedDaysHandlerTest extends TestCase
{
    #[Test]
    public function delegatesToRepositoryAndReturnsTotal(): void
    {
        $start = new DateTimeImmutable('2026-01-01');
        $end = new DateTimeImmutable('2026-12-31');

        /** @var VacationRepositoryInterface&MockObject $repository */
        $repository = $this->createMock(VacationRepositoryInterface::class);
        $repository->expects(self::once())->method('countApprovedDaysBetween')->with($start, $end)->willReturn(12.5);
        $handler = new CountApprovedDaysHandler($repository);

        self::assertSame(12.5, $handler(new CountApprovedDaysQuery($start, $end)));
    }

    #[Test]
    public function returnsZeroWhenRepositoryReportsNothing(): void
    {
        $stubRepository = $this->createStub(VacationRepositoryInterface::class);
        $stubRepository->method('countApprovedDaysBetween')->willReturn(0.0);
        $handler = new CountApprovedDaysHandler($stubRepository);

        $result = $handler(
            new CountApprovedDaysQuery(new DateTimeImmutable('2026-06-01'), new DateTimeImmutable('2026-06-30')),
        );

        self::assertSame(0.0, $result);
    }
}

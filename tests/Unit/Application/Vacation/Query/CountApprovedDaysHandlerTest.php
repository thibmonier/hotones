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
    private VacationRepositoryInterface&MockObject $repository;
    private CountApprovedDaysHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VacationRepositoryInterface::class);
        $this->handler    = new CountApprovedDaysHandler($this->repository);
    }

    #[Test]
    public function delegatesToRepositoryAndReturnsTotal(): void
    {
        $start = new DateTimeImmutable('2026-01-01');
        $end   = new DateTimeImmutable('2026-12-31');

        $this->repository->expects(self::once())
            ->method('countApprovedDaysBetween')
            ->with($start, $end)
            ->willReturn(12.5);

        self::assertSame(12.5, ($this->handler)(new CountApprovedDaysQuery($start, $end)));
    }

    #[Test]
    public function returnsZeroWhenRepositoryReportsNothing(): void
    {
        $this->repository->method('countApprovedDaysBetween')->willReturn(0.0);

        $result = ($this->handler)(new CountApprovedDaysQuery(
            new DateTimeImmutable('2026-06-01'),
            new DateTimeImmutable('2026-06-30'),
        ));

        self::assertSame(0.0, $result);
    }
}

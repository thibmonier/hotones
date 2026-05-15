<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\ValueObject;

use App\Domain\Project\ValueObject\DsoDays;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DsoDaysTest extends TestCase
{
    public function testFromDaysAcceptsZero(): void
    {
        $dso = DsoDays::fromDays(0.0);

        static::assertSame(0.0, $dso->getDays());
    }

    public function testFromDaysAcceptsPositiveValues(): void
    {
        $dso = DsoDays::fromDays(45.7);

        static::assertSame(45.7, $dso->getDays());
    }

    public function testFromDaysRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DSO days cannot be negative');

        DsoDays::fromDays(-1.0);
    }

    public function testEqualityByValue(): void
    {
        $a = DsoDays::fromDays(45.0);
        $b = DsoDays::fromDays(45.0);
        $c = DsoDays::fromDays(46.0);

        static::assertTrue($a->equals($b));
        static::assertFalse($a->equals($c));
    }

    public function testStringification(): void
    {
        $dso = DsoDays::fromDays(45.7);

        static::assertSame('45.7', (string) $dso);
    }

    public function testRoundedToOneDecimal(): void
    {
        $dso = DsoDays::fromDays(45.789);

        static::assertSame(45.8, $dso->getDays());
    }

    public function testZeroValueFactory(): void
    {
        $dso = DsoDays::zero();

        static::assertSame(0.0, $dso->getDays());
        static::assertTrue($dso->equals(DsoDays::fromDays(0.0)));
    }
}

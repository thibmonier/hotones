<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\EmploymentPeriod\ValueObject;

use App\Domain\EmploymentPeriod\ValueObject\WorkTimePercentage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WorkTimePercentageTest extends TestCase
{
    public function testFromFloatFullTime(): void
    {
        $p = WorkTimePercentage::fromFloat(100.0);
        static::assertSame(100.0, $p->getValue());
        static::assertTrue($p->isFullTime());
        static::assertSame(1.0, $p->asRatio());
    }

    public function testFromFloatPartTime(): void
    {
        $p = WorkTimePercentage::fromFloat(80.0);
        static::assertSame(80.0, $p->getValue());
        static::assertFalse($p->isFullTime());
        static::assertEqualsWithDelta(0.8, $p->asRatio(), 0.001);
    }

    public function testFullTimeFactory(): void
    {
        $p = WorkTimePercentage::fullTime();
        static::assertSame(100.0, $p->getValue());
        static::assertTrue($p->isFullTime());
    }

    public function testFromDecimalString(): void
    {
        $p = WorkTimePercentage::fromDecimalString('80.00');
        static::assertSame(80.0, $p->getValue());
    }

    public function testZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/strictly positive/');
        WorkTimePercentage::fromFloat(0.0);
    }

    public function testExceeds100Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot exceed/');
        WorkTimePercentage::fromFloat(100.5);
    }

    public function testEqualsTrueWithSameValue(): void
    {
        $a = WorkTimePercentage::fromFloat(80.0);
        $b = WorkTimePercentage::fromFloat(80.0);
        static::assertTrue($a->equals($b));
    }
}

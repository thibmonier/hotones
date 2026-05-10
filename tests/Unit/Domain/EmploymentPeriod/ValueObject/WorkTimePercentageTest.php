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
        self::assertSame(100.0, $p->getValue());
        self::assertTrue($p->isFullTime());
        self::assertSame(1.0, $p->asRatio());
    }

    public function testFromFloatPartTime(): void
    {
        $p = WorkTimePercentage::fromFloat(80.0);
        self::assertSame(80.0, $p->getValue());
        self::assertFalse($p->isFullTime());
        self::assertEqualsWithDelta(0.8, $p->asRatio(), 0.001);
    }

    public function testFullTimeFactory(): void
    {
        $p = WorkTimePercentage::fullTime();
        self::assertSame(100.0, $p->getValue());
        self::assertTrue($p->isFullTime());
    }

    public function testFromDecimalString(): void
    {
        $p = WorkTimePercentage::fromDecimalString('80.00');
        self::assertSame(80.0, $p->getValue());
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
        self::assertTrue($a->equals($b));
    }
}

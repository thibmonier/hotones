<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\TimeConversionService;
use DateTime;
use PHPUnit\Framework\TestCase;

class TimeConversionServiceTest extends TestCase
{
    public function testHoursToDays(): void
    {
        static::assertSame('2.00', TimeConversionService::hoursToDays('16'));
        static::assertSame('1.25', TimeConversionService::hoursToDays('10'));
    }

    public function testDaysToHours(): void
    {
        static::assertSame('10.00', TimeConversionService::daysToHours('1.25'));
        static::assertSame('8.00', TimeConversionService::daysToHours('1'));
    }

    public function testFormatHoursForDisplay(): void
    {
        static::assertSame('7,5h', TimeConversionService::formatHoursForDisplay('7.5'));
        static::assertSame('1,0j', TimeConversionService::formatHoursForDisplay('8'));
        static::assertSame('1,2j 2,0h', TimeConversionService::formatHoursForDisplay('10'));
    }

    public function testFormatDaysForDisplay(): void
    {
        static::assertSame('2,5j', TimeConversionService::formatDaysForDisplay('2.5'));
    }

    public function testParseUserInput(): void
    {
        $res = TimeConversionService::parseUserInput('8h');
        static::assertSame('8', $res['hours']);
        static::assertSame('1.00', $res['days']);

        $res = TimeConversionService::parseUserInput('1.5j');
        static::assertSame('1.5', $res['days']);
        static::assertSame('12.00', $res['hours']);

        $res = TimeConversionService::parseUserInput('10');
        static::assertSame('10', $res['hours']);
        static::assertSame('1.25', $res['days']);
    }

    public function testWorkingDaysBetweenAndTheoreticalHours(): void
    {
        $start = new DateTime('2024-04-01'); // Monday
        $end = new DateTime('2024-04-05'); // Friday
        static::assertSame(5, TimeConversionService::getWorkingDaysBetween($start, $end));
        static::assertSame('40.00', TimeConversionService::getTheoreticalHours($start, $end));

        // Span with weekend inside
        $start = new DateTime('2024-04-04'); // Thu
        $end = new DateTime('2024-04-10'); // Wed
        static::assertSame(5, TimeConversionService::getWorkingDaysBetween($start, $end));
    }
}

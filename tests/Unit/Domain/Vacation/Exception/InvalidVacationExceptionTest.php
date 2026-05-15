<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\Exception;

use App\Domain\Vacation\Exception\InvalidVacationException;
use DomainException;
use PHPUnit\Framework\TestCase;

final class InvalidVacationExceptionTest extends TestCase
{
    public function testEndDateBeforeStartDateFactoryBuildsException(): void
    {
        $exception = InvalidVacationException::endDateBeforeStartDate();

        static::assertInstanceOf(InvalidVacationException::class, $exception);
        static::assertInstanceOf(DomainException::class, $exception);
    }

    public function testEndDateBeforeStartDateMessageFormat(): void
    {
        $exception = InvalidVacationException::endDateBeforeStartDate();

        static::assertStringContainsString('End date', $exception->getMessage());
        static::assertStringContainsString('start date', $exception->getMessage());
    }
}

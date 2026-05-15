<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\Exception;

use App\Domain\Vacation\Exception\VacationNotFoundException;
use App\Domain\Vacation\ValueObject\VacationId;
use DomainException;
use PHPUnit\Framework\TestCase;

final class VacationNotFoundExceptionTest extends TestCase
{
    public function testWithIdFactoryBuildsException(): void
    {
        $vacationId = VacationId::generate();

        $exception = VacationNotFoundException::withId($vacationId);

        static::assertInstanceOf(VacationNotFoundException::class, $exception);
        static::assertInstanceOf(DomainException::class, $exception);
    }

    public function testWithIdMessageContainsVacationId(): void
    {
        $vacationId = VacationId::generate();

        $exception = VacationNotFoundException::withId($vacationId);

        static::assertStringContainsString($vacationId->getValue(), $exception->getMessage());
        static::assertStringContainsString('Vacation not found', $exception->getMessage());
    }
}

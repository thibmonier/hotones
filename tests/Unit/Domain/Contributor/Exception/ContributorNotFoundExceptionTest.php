<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Contributor\Exception;

use App\Domain\Contributor\Exception\ContributorNotFoundException;
use App\Domain\Contributor\ValueObject\ContributorId;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ContributorNotFoundExceptionTest extends TestCase
{
    public function testWithIdFactoryBuildsException(): void
    {
        $id = ContributorId::generate();

        $exception = ContributorNotFoundException::withId($id);

        self::assertInstanceOf(ContributorNotFoundException::class, $exception);
        self::assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testWithIdMessageContainsId(): void
    {
        $id = ContributorId::generate();

        $exception = ContributorNotFoundException::withId($id);

        self::assertStringContainsString((string) $id, $exception->getMessage());
        self::assertStringContainsString('not found', $exception->getMessage());
    }
}

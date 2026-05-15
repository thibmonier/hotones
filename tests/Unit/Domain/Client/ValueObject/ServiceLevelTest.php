<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Client\ValueObject;

use App\Domain\Client\ValueObject\ServiceLevel;
use PHPUnit\Framework\TestCase;

final class ServiceLevelTest extends TestCase
{
    public function testCases(): void
    {
        static::assertSame('standard', ServiceLevel::STANDARD->value);
        static::assertSame('premium', ServiceLevel::PREMIUM->value);
        static::assertSame('enterprise', ServiceLevel::ENTERPRISE->value);
    }

    public function testGetLabel(): void
    {
        static::assertSame('Standard', ServiceLevel::STANDARD->getLabel());
        static::assertSame('Premium', ServiceLevel::PREMIUM->getLabel());
        static::assertSame('Enterprise', ServiceLevel::ENTERPRISE->getLabel());
    }

    public function testIsPremiumOrHigher(): void
    {
        static::assertFalse(ServiceLevel::STANDARD->isPremiumOrHigher());
        static::assertTrue(ServiceLevel::PREMIUM->isPremiumOrHigher());
        static::assertTrue(ServiceLevel::ENTERPRISE->isPremiumOrHigher());
    }
}

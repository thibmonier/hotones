<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Client\ValueObject;

use App\Domain\Client\ValueObject\ServiceLevel;
use PHPUnit\Framework\TestCase;

final class ServiceLevelTest extends TestCase
{
    public function testCases(): void
    {
        $this->assertSame('standard', ServiceLevel::STANDARD->value);
        $this->assertSame('premium', ServiceLevel::PREMIUM->value);
        $this->assertSame('enterprise', ServiceLevel::ENTERPRISE->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Standard', ServiceLevel::STANDARD->getLabel());
        $this->assertSame('Premium', ServiceLevel::PREMIUM->getLabel());
        $this->assertSame('Enterprise', ServiceLevel::ENTERPRISE->getLabel());
    }

    public function testIsPremiumOrHigher(): void
    {
        $this->assertFalse(ServiceLevel::STANDARD->isPremiumOrHigher());
        $this->assertTrue(ServiceLevel::PREMIUM->isPremiumOrHigher());
        $this->assertTrue(ServiceLevel::ENTERPRISE->isPremiumOrHigher());
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\ValueObject;

use App\Domain\Project\ValueObject\ProjectType;
use PHPUnit\Framework\TestCase;

final class ProjectTypeTest extends TestCase
{
    public function testCases(): void
    {
        static::assertSame('forfait', ProjectType::FORFAIT->value);
        static::assertSame('regie', ProjectType::REGIE->value);
    }

    public function testIsFixedPrice(): void
    {
        static::assertTrue(ProjectType::FORFAIT->isFixedPrice());
        static::assertFalse(ProjectType::REGIE->isFixedPrice());
    }

    public function testIsTimeAndMaterials(): void
    {
        static::assertTrue(ProjectType::REGIE->isTimeAndMaterials());
        static::assertFalse(ProjectType::FORFAIT->isTimeAndMaterials());
    }

    public function testLabel(): void
    {
        static::assertSame('Forfait', ProjectType::FORFAIT->label());
        static::assertSame('Régie', ProjectType::REGIE->label());
    }
}

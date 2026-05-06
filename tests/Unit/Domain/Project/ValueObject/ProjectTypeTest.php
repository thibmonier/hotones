<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\ValueObject;

use App\Domain\Project\ValueObject\ProjectType;
use PHPUnit\Framework\TestCase;

final class ProjectTypeTest extends TestCase
{
    public function testCases(): void
    {
        $this->assertSame('forfait', ProjectType::FORFAIT->value);
        $this->assertSame('regie', ProjectType::REGIE->value);
    }

    public function testIsFixedPrice(): void
    {
        $this->assertTrue(ProjectType::FORFAIT->isFixedPrice());
        $this->assertFalse(ProjectType::REGIE->isFixedPrice());
    }

    public function testIsTimeAndMaterials(): void
    {
        $this->assertTrue(ProjectType::REGIE->isTimeAndMaterials());
        $this->assertFalse(ProjectType::FORFAIT->isTimeAndMaterials());
    }

    public function testLabel(): void
    {
        $this->assertSame('Forfait', ProjectType::FORFAIT->label());
        $this->assertSame('Régie', ProjectType::REGIE->label());
    }
}

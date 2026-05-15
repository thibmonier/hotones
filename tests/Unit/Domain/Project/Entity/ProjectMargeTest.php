<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Entity\Project;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectType;
use App\Domain\Shared\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class ProjectMargeTest extends TestCase
{
    public function testMargeNullBeforeSnapshot(): void
    {
        $project = $this->makeProject();

        static::assertNull($project->getMargeAbsoluteCents());
        static::assertNull($project->getMargePercent());
        static::assertNull($project->getCoutTotal());
        static::assertNull($project->getFactureTotal());
        static::assertNull($project->getMargeCalculatedAt());
    }

    public function testSetMargeSnapshotPositiveMargin(): void
    {
        $project = $this->makeProject();
        $cout = Money::fromAmount(8000.00);
        $facture = Money::fromAmount(10_000.00);

        $project->setMargeSnapshot($cout, $facture);

        static::assertSame(8000.0, $project->getCoutTotal()?->getAmount());
        static::assertSame(10_000.0, $project->getFactureTotal()?->getAmount());
        static::assertSame(200_000, $project->getMargeAbsoluteCents()); // 2000.00 €
        static::assertSame(20.0, $project->getMargePercent());
        static::assertNotNull($project->getMargeCalculatedAt());
    }

    public function testSetMargeSnapshotNegativeMargin(): void
    {
        $project = $this->makeProject();
        $cout = Money::fromAmount(12_000.00);
        $facture = Money::fromAmount(10_000.00);

        $project->setMargeSnapshot($cout, $facture);

        // marge = 10000 - 12000 = -2000 → cents = -200000 → percent = -20 %
        static::assertSame(-200_000, $project->getMargeAbsoluteCents());
        static::assertSame(-20.0, $project->getMargePercent());
    }

    public function testGetMargePercentNullWhenFactureZero(): void
    {
        $project = $this->makeProject();
        $project->setMargeSnapshot(Money::fromAmount(5000.00), Money::zero());

        static::assertNull($project->getMargePercent());
    }

    public function testHasMargeBelowThresholdTrueWhenBelow(): void
    {
        $project = $this->makeProject();
        $project->setMargeSnapshot(Money::fromAmount(9300.00), Money::fromAmount(10_000.00));

        // marge = 700 / 10000 = 7 % < 10 %
        static::assertTrue($project->hasMargeBelowThreshold(10.0));
    }

    public function testHasMargeBelowThresholdFalseWhenAbove(): void
    {
        $project = $this->makeProject();
        $project->setMargeSnapshot(Money::fromAmount(8000.00), Money::fromAmount(10_000.00));

        // marge = 2000 / 10000 = 20 % > 10 %
        static::assertFalse($project->hasMargeBelowThreshold(10.0));
    }

    public function testHasMargeBelowThresholdFalseBeforeSnapshot(): void
    {
        $project = $this->makeProject();

        static::assertFalse($project->hasMargeBelowThreshold(10.0));
    }

    private function makeProject(): Project
    {
        return Project::create(
            id: ProjectId::generate(),
            name: 'Refonte Site E-Commerce',
            clientId: ClientId::generate(),
            projectType: ProjectType::FORFAIT,
        );
    }
}

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

        self::assertNull($project->getMargeAbsoluteCents());
        self::assertNull($project->getMargePercent());
        self::assertNull($project->getCoutTotal());
        self::assertNull($project->getFactureTotal());
        self::assertNull($project->getMargeCalculatedAt());
    }

    public function testSetMargeSnapshotPositiveMargin(): void
    {
        $project = $this->makeProject();
        $cout = Money::fromAmount(8000.00);
        $facture = Money::fromAmount(10000.00);

        $project->setMargeSnapshot($cout, $facture);

        self::assertSame(8000.0, $project->getCoutTotal()?->getAmount());
        self::assertSame(10000.0, $project->getFactureTotal()?->getAmount());
        self::assertSame(200000, $project->getMargeAbsoluteCents()); // 2000.00 €
        self::assertSame(20.0, $project->getMargePercent());
        self::assertNotNull($project->getMargeCalculatedAt());
    }

    public function testSetMargeSnapshotNegativeMargin(): void
    {
        $project = $this->makeProject();
        $cout = Money::fromAmount(12000.00);
        $facture = Money::fromAmount(10000.00);

        $project->setMargeSnapshot($cout, $facture);

        // marge = 10000 - 12000 = -2000 → cents = -200000 → percent = -20 %
        self::assertSame(-200000, $project->getMargeAbsoluteCents());
        self::assertSame(-20.0, $project->getMargePercent());
    }

    public function testGetMargePercentNullWhenFactureZero(): void
    {
        $project = $this->makeProject();
        $project->setMargeSnapshot(Money::fromAmount(5000.00), Money::zero());

        self::assertNull($project->getMargePercent());
    }

    public function testHasMargeBelowThresholdTrueWhenBelow(): void
    {
        $project = $this->makeProject();
        $project->setMargeSnapshot(Money::fromAmount(9300.00), Money::fromAmount(10000.00));

        // marge = 700 / 10000 = 7 % < 10 %
        self::assertTrue($project->hasMargeBelowThreshold(10.0));
    }

    public function testHasMargeBelowThresholdFalseWhenAbove(): void
    {
        $project = $this->makeProject();
        $project->setMargeSnapshot(Money::fromAmount(8000.00), Money::fromAmount(10000.00));

        // marge = 2000 / 10000 = 20 % > 10 %
        self::assertFalse($project->hasMargeBelowThreshold(10.0));
    }

    public function testHasMargeBelowThresholdFalseBeforeSnapshot(): void
    {
        $project = $this->makeProject();

        self::assertFalse($project->hasMargeBelowThreshold(10.0));
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

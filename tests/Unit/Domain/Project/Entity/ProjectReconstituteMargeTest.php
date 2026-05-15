<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Entity\Project;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectType;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Sprint-023 sub-epic D US-107 — verify reconstitute() restores margin
 * snapshot from extra array (ACL translator flat→DDD path).
 */
final class ProjectReconstituteMargeTest extends TestCase
{
    public function testReconstituteRestoresMargeFromExtraArray(): void
    {
        $calculatedAt = new DateTimeImmutable('2026-05-11 10:00:00');

        $project = Project::reconstitute(
            id: ProjectId::generate(),
            name: 'Reconstituted Project',
            clientId: ClientId::generate(),
            projectType: ProjectType::FORFAIT,
            isInternal: false,
            extra: [
                'coutTotal' => Money::fromAmount(5000.00),
                'factureTotal' => Money::fromAmount(10_000.00),
                'margeCalculatedAt' => $calculatedAt,
            ],
        );

        static::assertSame(5000.0, $project->getCoutTotal()?->getAmount());
        static::assertSame(10_000.0, $project->getFactureTotal()?->getAmount());
        static::assertSame(500_000, $project->getMargeAbsoluteCents()); // 5000 € en centimes
        static::assertSame(50.0, $project->getMargePercent());
        static::assertSame($calculatedAt, $project->getMargeCalculatedAt());
    }

    public function testReconstituteWithoutMargeExtraLeavesNull(): void
    {
        $project = Project::reconstitute(
            id: ProjectId::generate(),
            name: 'No Marge',
            clientId: ClientId::generate(),
            projectType: ProjectType::FORFAIT,
            isInternal: false,
            extra: [],
        );

        static::assertNull($project->getCoutTotal());
        static::assertNull($project->getFactureTotal());
        static::assertNull($project->getMargeCalculatedAt());
    }

    public function testReconstituteIgnoresInvalidMargeTypes(): void
    {
        $project = Project::reconstitute(
            id: ProjectId::generate(),
            name: 'Invalid types',
            clientId: ClientId::generate(),
            projectType: ProjectType::FORFAIT,
            isInternal: false,
            extra: [
                'coutTotal' => 'not-a-money', // bad type
                'margeCalculatedAt' => '2026-01-01', // bad type (string)
            ],
        );

        static::assertNull($project->getCoutTotal());
        static::assertNull($project->getMargeCalculatedAt());
    }
}

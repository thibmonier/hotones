<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Project\Translator;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Entity\Project as DddProject;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectType;
use App\Domain\Shared\ValueObject\Money;
use App\Entity\Project as FlatProject;
use App\Infrastructure\Project\Translator\ProjectDddToFlatTranslator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Sprint-023 sub-epic D US-107 — persistence margin snapshot.
 *
 * Tests Unit translator DDD → Flat pour vérifier que `setMargeSnapshot`
 * (US-104 sprint-022) persiste correctement dans `coutTotalCents` +
 * `factureTotalCents` + `margeCalculatedAt` flat fields.
 */
final class ProjectMarginPersistenceTest extends TestCase
{
    public function testApplyToPersistsMargeSnapshotToFlatFields(): void
    {
        $ddd = $this->makeProject();
        $ddd->setMargeSnapshot(
            coutTotal: Money::fromAmount(8000.00),
            factureTotal: Money::fromAmount(10_000.00),
        );

        $flat = new FlatProject();
        $translator = new ProjectDddToFlatTranslator();

        $translator->applyTo($ddd, $flat);

        // 8000 € = 800000 centimes
        static::assertSame(800_000, $flat->coutTotalCents);
        static::assertSame(1_000_000, $flat->factureTotalCents);
        static::assertNotNull($flat->margeCalculatedAt);
        static::assertInstanceOf(DateTimeImmutable::class, $flat->margeCalculatedAt);
    }

    public function testApplyToPersistsNullWhenSnapshotNotSet(): void
    {
        $ddd = $this->makeProject();
        // No setMargeSnapshot call

        $flat = new FlatProject();
        $flat->coutTotalCents = 999_999; // Pre-existing stale value
        $flat->factureTotalCents = 888_888;
        $flat->margeCalculatedAt = new DateTimeImmutable('2020-01-01');

        $translator = new ProjectDddToFlatTranslator();
        $translator->applyTo($ddd, $flat);

        // Snapshot null → flat fields cleared
        static::assertNull($flat->coutTotalCents);
        static::assertNull($flat->factureTotalCents);
        static::assertNull($flat->margeCalculatedAt);
    }

    public function testApplyToOverwritesExistingFlatMargeValues(): void
    {
        $ddd = $this->makeProject();
        $ddd->setMargeSnapshot(
            coutTotal: Money::fromAmount(5000.00),
            factureTotal: Money::fromAmount(7000.00),
        );

        $flat = new FlatProject();
        $flat->coutTotalCents = 999_999;
        $flat->factureTotalCents = 888_888;

        $translator = new ProjectDddToFlatTranslator();
        $translator->applyTo($ddd, $flat);

        static::assertSame(500_000, $flat->coutTotalCents);
        static::assertSame(700_000, $flat->factureTotalCents);
    }

    private function makeProject(): DddProject
    {
        return DddProject::create(
            id: ProjectId::generate(),
            name: 'Test Project',
            clientId: ClientId::generate(),
            projectType: ProjectType::FORFAIT,
        );
    }
}

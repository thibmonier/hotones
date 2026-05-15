<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\WorkItem\Translator;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\WorkItem\Entity\WorkItem as DddWorkItem;
use App\Domain\WorkItem\ValueObject\HourlyRate;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use App\Entity\Contributor as FlatContributor;
use App\Entity\Project as FlatProject;
use App\Entity\Timesheet as FlatTimesheet;
use App\Infrastructure\WorkItem\Translator\WorkItemDddToFlatTranslator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Sprint-020 EPIC-003 Phase 2 (US-098) — coverage Unit du translator
 * DDD WorkItem → flat Timesheet (sync partielle hours + notes).
 */
final class WorkItemDddToFlatTranslatorTest extends TestCase
{
    public function testApplyToSyncsHoursAndNotes(): void
    {
        $ddd = $this->makeWorkItem(hours: 8.0);
        $ddd->setNotes('synced notes');

        $flat = $this->makeFlatTimesheet();
        $flat->hours = '7.50';
        $flat->notes = 'old notes';

        new WorkItemDddToFlatTranslator()->applyTo($ddd, $flat);

        static::assertSame('8', $flat->hours);
        static::assertSame('synced notes', $flat->notes);
    }

    public function testApplyToWithNullNotesClears(): void
    {
        $ddd = $this->makeWorkItem();
        // notes par défaut null

        $flat = $this->makeFlatTimesheet();
        $flat->notes = 'some legacy note';

        new WorkItemDddToFlatTranslator()->applyTo($ddd, $flat);

        static::assertNull($flat->notes);
    }

    public function testApplyToDoesNotTouchStructuralFields(): void
    {
        // ID, contributor, project, task, date côté flat ne sont JAMAIS écrasés.
        $ddd = $this->makeWorkItem();

        $flat = $this->makeFlatTimesheet();
        $flat->date = new DateTimeImmutable('2025-01-01');

        $originalId = $flat->getId();
        $originalContributorId = $flat->contributor->getId();
        $originalProjectId = $flat->project->getId();
        $originalDate = $flat->date;

        new WorkItemDddToFlatTranslator()->applyTo($ddd, $flat);

        static::assertSame($originalId, $flat->getId());
        static::assertSame($originalContributorId, $flat->contributor->getId());
        static::assertSame($originalProjectId, $flat->project->getId());
        static::assertSame($originalDate, $flat->date);
    }

    public function testApplyToReviseHoursPropagates(): void
    {
        $ddd = $this->makeWorkItem(hours: 7.5);

        $flat = $this->makeFlatTimesheet();
        $flat->hours = '7.50';

        $ddd->reviseHours(WorkedHours::fromFloat(8.0));

        new WorkItemDddToFlatTranslator()->applyTo($ddd, $flat);

        static::assertSame('8', $flat->hours);
    }

    private function makeWorkItem(float $hours = 7.5): DddWorkItem
    {
        return DddWorkItem::create(
            WorkItemId::fromLegacyInt(42),
            ProjectId::fromLegacyInt(11),
            ContributorId::fromLegacyInt(7),
            new DateTimeImmutable('2026-04-15'),
            WorkedHours::fromFloat($hours),
            HourlyRate::fromAmount(50.0),
            HourlyRate::fromAmount(100.0),
        );
    }

    private function makeFlatTimesheet(): FlatTimesheet
    {
        $project = new FlatProject();
        new ReflectionProperty(FlatProject::class, 'id')->setValue($project, 11);

        $contributor = new FlatContributor();
        new ReflectionProperty(FlatContributor::class, 'id')->setValue($contributor, 7);

        $flat = new FlatTimesheet();
        new ReflectionProperty(FlatTimesheet::class, 'id')->setValue($flat, 42);
        $flat->project = $project;
        $flat->contributor = $contributor;
        $flat->date = new DateTimeImmutable('2026-04-15');
        $flat->hours = '7.50';

        return $flat;
    }
}

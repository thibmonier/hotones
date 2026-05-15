<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\WorkItem\Translator;

use App\Entity\Contributor as FlatContributor;
use App\Entity\Project as FlatProject;
use App\Entity\ProjectTask as FlatProjectTask;
use App\Entity\Timesheet as FlatTimesheet;
use App\Infrastructure\WorkItem\Translator\WorkItemFlatToDddTranslator;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

/**
 * Sprint-020 EPIC-003 Phase 2 (US-098) — coverage Unit du translator
 * Timesheet flat → DDD WorkItem.
 */
final class WorkItemFlatToDddTranslatorTest extends TestCase
{
    public function testTranslateBasicTimesheetWithoutTask(): void
    {
        $flat = $this->makeTimesheet(taskId: null);
        $translator = new WorkItemFlatToDddTranslator();

        $workItem = $translator->translate($flat);

        static::assertSame('legacy:42', $workItem->getId()->getValue());
        static::assertSame(11, $workItem->getProjectId()->toLegacyInt());
        static::assertSame(7, $workItem->getContributorId()->toLegacyInt());
        static::assertNull($workItem->getTaskId(), 'task=NULL → DDD taskId null (ADR-0015 Q1)');
        static::assertSame(7.5, $workItem->getHours()->getValue());
        // costRate 400/8 = 50, 7.5h × 50 = 375 EUR = 37500 cents
        static::assertSame(37_500, $workItem->cost()->getAmountCents());
        // billedRate 800/8 = 100, 7.5h × 100 = 750 EUR
        static::assertSame(75_000, $workItem->revenue()->getAmountCents());
    }

    public function testTranslateWithTask(): void
    {
        $flat = $this->makeTimesheet(taskId: 33);
        $translator = new WorkItemFlatToDddTranslator();

        $workItem = $translator->translate($flat);

        static::assertNotNull($workItem->getTaskId());
        static::assertSame(33, $workItem->getTaskId()->toLegacyInt());
    }

    public function testTranslatePreservesNotes(): void
    {
        $flat = $this->makeTimesheet(notes: 'client X meeting');
        $translator = new WorkItemFlatToDddTranslator();

        $workItem = $translator->translate($flat);

        static::assertSame('client X meeting', $workItem->getNotes());
    }

    public function testTranslateUnsavedTimesheetThrows(): void
    {
        $flat = $this->makeTimesheet(id: null);
        $translator = new WorkItemFlatToDddTranslator();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/unsaved/');
        $translator->translate($flat);
    }

    public function testTranslateContributorWithoutCjmThrows(): void
    {
        // Risk Q3 mitigation : translator throw si cjm null.
        $flat = $this->makeTimesheet(contributorCjm: null);
        $translator = new WorkItemFlatToDddTranslator();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/null\/empty daily rate/');
        $translator->translate($flat);
    }

    public function testTranslateContributorWithoutTjmThrows(): void
    {
        $flat = $this->makeTimesheet(contributorTjm: null);
        $translator = new WorkItemFlatToDddTranslator();

        $this->expectException(InvalidArgumentException::class);
        $translator->translate($flat);
    }

    public function testTranslateContributorWithZeroCjmThrows(): void
    {
        $flat = $this->makeTimesheet(contributorCjm: '0');
        $translator = new WorkItemFlatToDddTranslator();

        $this->expectException(InvalidArgumentException::class);
        $translator->translate($flat);
    }

    /**
     * Construit un Timesheet flat in-memory sans persist.
     */
    private function makeTimesheet(
        ?int $id = 42,
        int $projectId = 11,
        int $contributorId = 7,
        ?int $taskId = null,
        ?string $contributorCjm = '400.00',
        ?string $contributorTjm = '800.00',
        ?string $notes = null,
        string $hours = '7.50',
    ): FlatTimesheet {
        $project = new FlatProject();
        new ReflectionProperty(FlatProject::class, 'id')->setValue($project, $projectId);

        $contributor = new FlatContributor();
        new ReflectionProperty(FlatContributor::class, 'id')->setValue($contributor, $contributorId);
        $contributor->cjm = $contributorCjm;
        $contributor->tjm = $contributorTjm;

        $task = null;
        if ($taskId !== null) {
            $task = new FlatProjectTask();
            new ReflectionProperty(FlatProjectTask::class, 'id')->setValue($task, $taskId);
        }

        $flat = new FlatTimesheet();
        if ($id !== null) {
            new ReflectionProperty(FlatTimesheet::class, 'id')->setValue($flat, $id);
        }
        $flat->project = $project;
        $flat->contributor = $contributor;
        $flat->task = $task;
        $flat->date = new DateTimeImmutable('2026-04-15');
        $flat->hours = $hours;
        $flat->notes = $notes;

        return $flat;
    }
}

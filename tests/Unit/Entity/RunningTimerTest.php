<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\Project;
use App\Entity\ProjectSubTask;
use App\Entity\ProjectTask;
use App\Entity\RunningTimer;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RunningTimer entity.
 *
 * Covers the in-memory invariants of a timer:
 *  - isActive(): true while stoppedAt is null, false once set.
 *  - getters/setters chain (fluent setters return self).
 *  - company / contributor / project / task / subTask wiring.
 *  - default state on instantiation (no id, no stoppedAt).
 *
 * Persistence-level invariants (1 active timer per contributor) are exercised
 * in the integration RunningTimerRepositoryTest.
 */
final class RunningTimerTest extends TestCase
{
    #[Test]
    public function newlyConstructedTimerHasNoIdAndNoStoppedAt(): void
    {
        $timer = new RunningTimer();

        self::assertNull($timer->getId());
        self::assertNull($timer->getStoppedAt());
    }

    #[Test]
    public function isActiveIsTrueWhenStoppedAtIsNull(): void
    {
        $timer = new RunningTimer()->setStartedAt(new DateTime('2026-04-30 10:00:00'));

        self::assertTrue($timer->isActive());
    }

    #[Test]
    public function isActiveBecomesFalseOnceStoppedAtIsSet(): void
    {
        $timer = new RunningTimer()
            ->setStartedAt(new DateTime('2026-04-30 10:00:00'))
            ->setStoppedAt(new DateTime('2026-04-30 11:30:00'));

        self::assertFalse($timer->isActive());
    }

    #[Test]
    public function settersAreFluent(): void
    {
        $company = new Company();
        $contributor = new Contributor();
        $project = new Project();
        $task = new ProjectTask();
        $subTask = new ProjectSubTask();
        $startedAt = new DateTimeImmutable('2026-04-30 09:15:00');

        $timer = new RunningTimer();

        self::assertSame($timer, $timer->setCompany($company));
        self::assertSame($timer, $timer->setContributor($contributor));
        self::assertSame($timer, $timer->setProject($project));
        self::assertSame($timer, $timer->setTask($task));
        self::assertSame($timer, $timer->setSubTask($subTask));
        self::assertSame($timer, $timer->setStartedAt($startedAt));
        self::assertSame($timer, $timer->setStoppedAt(null));
    }

    #[Test]
    public function gettersReturnAssignedValues(): void
    {
        $company = new Company();
        $contributor = new Contributor();
        $project = new Project();
        $task = new ProjectTask();
        $subTask = new ProjectSubTask();
        $startedAt = new DateTimeImmutable('2026-04-30 09:15:00');
        $stoppedAt = new DateTimeImmutable('2026-04-30 12:00:00');

        $timer = new RunningTimer()
            ->setCompany($company)
            ->setContributor($contributor)
            ->setProject($project)
            ->setTask($task)
            ->setSubTask($subTask)
            ->setStartedAt($startedAt)
            ->setStoppedAt($stoppedAt);

        self::assertSame($company, $timer->getCompany());
        self::assertSame($contributor, $timer->getContributor());
        self::assertSame($project, $timer->getProject());
        self::assertSame($task, $timer->getTask());
        self::assertSame($subTask, $timer->getSubTask());
        self::assertSame($startedAt, $timer->getStartedAt());
        self::assertSame($stoppedAt, $timer->getStoppedAt());
    }

    #[Test]
    public function taskAndSubTaskAreOptional(): void
    {
        $timer = new RunningTimer()
            ->setStartedAt(new DateTime('2026-04-30 10:00:00'))
            ->setTask(null)
            ->setSubTask(null);

        self::assertNull($timer->getTask());
        self::assertNull($timer->getSubTask());
    }

    #[Test]
    public function stoppingARunningTimerPreservesStartedAt(): void
    {
        $startedAt = new DateTime('2026-04-30 10:00:00');
        $timer = new RunningTimer()->setStartedAt($startedAt);

        $timer->setStoppedAt(new DateTime('2026-04-30 11:00:00'));

        self::assertSame($startedAt, $timer->getStartedAt());
    }
}

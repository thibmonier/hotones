<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\ValueObject;

use App\Domain\Project\ValueObject\ProjectStatus;
use PHPUnit\Framework\TestCase;

final class ProjectStatusTest extends TestCase
{
    public function testCases(): void
    {
        static::assertSame('draft', ProjectStatus::DRAFT->value);
        static::assertSame('active', ProjectStatus::ACTIVE->value);
        static::assertSame('on_hold', ProjectStatus::ON_HOLD->value);
        static::assertSame('completed', ProjectStatus::COMPLETED->value);
        static::assertSame('cancelled', ProjectStatus::CANCELLED->value);
    }

    public function testAllowedTransitionsFromDraft(): void
    {
        static::assertSame(
            [ProjectStatus::ACTIVE, ProjectStatus::CANCELLED],
            ProjectStatus::DRAFT->allowedTransitions(),
        );
    }

    public function testAllowedTransitionsFromActive(): void
    {
        static::assertSame(
            [ProjectStatus::ON_HOLD, ProjectStatus::COMPLETED, ProjectStatus::CANCELLED],
            ProjectStatus::ACTIVE->allowedTransitions(),
        );
    }

    public function testAllowedTransitionsFromCompletedAreEmpty(): void
    {
        static::assertSame([], ProjectStatus::COMPLETED->allowedTransitions());
        static::assertSame([], ProjectStatus::CANCELLED->allowedTransitions());
    }

    public function testCanTransitionTo(): void
    {
        static::assertTrue(ProjectStatus::DRAFT->canTransitionTo(ProjectStatus::ACTIVE));
        static::assertFalse(ProjectStatus::DRAFT->canTransitionTo(ProjectStatus::COMPLETED));
        static::assertTrue(ProjectStatus::ACTIVE->canTransitionTo(ProjectStatus::COMPLETED));
        static::assertFalse(ProjectStatus::COMPLETED->canTransitionTo(ProjectStatus::ACTIVE));
    }

    public function testIsActive(): void
    {
        static::assertTrue(ProjectStatus::ACTIVE->isActive());
        static::assertFalse(ProjectStatus::DRAFT->isActive());
    }

    public function testIsClosed(): void
    {
        static::assertTrue(ProjectStatus::COMPLETED->isClosed());
        static::assertTrue(ProjectStatus::CANCELLED->isClosed());
        static::assertFalse(ProjectStatus::ACTIVE->isClosed());
    }
}

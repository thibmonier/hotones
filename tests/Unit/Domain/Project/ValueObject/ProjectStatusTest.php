<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\ValueObject;

use App\Domain\Project\ValueObject\ProjectStatus;
use PHPUnit\Framework\TestCase;

final class ProjectStatusTest extends TestCase
{
    public function testCases(): void
    {
        $this->assertSame('draft', ProjectStatus::DRAFT->value);
        $this->assertSame('active', ProjectStatus::ACTIVE->value);
        $this->assertSame('on_hold', ProjectStatus::ON_HOLD->value);
        $this->assertSame('completed', ProjectStatus::COMPLETED->value);
        $this->assertSame('cancelled', ProjectStatus::CANCELLED->value);
    }

    public function testAllowedTransitionsFromDraft(): void
    {
        $this->assertSame(
            [ProjectStatus::ACTIVE, ProjectStatus::CANCELLED],
            ProjectStatus::DRAFT->allowedTransitions(),
        );
    }

    public function testAllowedTransitionsFromActive(): void
    {
        $this->assertSame(
            [ProjectStatus::ON_HOLD, ProjectStatus::COMPLETED, ProjectStatus::CANCELLED],
            ProjectStatus::ACTIVE->allowedTransitions(),
        );
    }

    public function testAllowedTransitionsFromCompletedAreEmpty(): void
    {
        $this->assertSame([], ProjectStatus::COMPLETED->allowedTransitions());
        $this->assertSame([], ProjectStatus::CANCELLED->allowedTransitions());
    }

    public function testCanTransitionTo(): void
    {
        $this->assertTrue(ProjectStatus::DRAFT->canTransitionTo(ProjectStatus::ACTIVE));
        $this->assertFalse(ProjectStatus::DRAFT->canTransitionTo(ProjectStatus::COMPLETED));
        $this->assertTrue(ProjectStatus::ACTIVE->canTransitionTo(ProjectStatus::COMPLETED));
        $this->assertFalse(ProjectStatus::COMPLETED->canTransitionTo(ProjectStatus::ACTIVE));
    }

    public function testIsActive(): void
    {
        $this->assertTrue(ProjectStatus::ACTIVE->isActive());
        $this->assertFalse(ProjectStatus::DRAFT->isActive());
    }

    public function testIsClosed(): void
    {
        $this->assertTrue(ProjectStatus::COMPLETED->isClosed());
        $this->assertTrue(ProjectStatus::CANCELLED->isClosed());
        $this->assertFalse(ProjectStatus::ACTIVE->isClosed());
    }
}

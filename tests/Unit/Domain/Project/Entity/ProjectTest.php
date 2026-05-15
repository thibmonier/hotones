<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project\Entity;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Entity\Project;
use App\Domain\Project\Event\ProjectCreatedEvent;
use App\Domain\Project\Event\ProjectStatusChangedEvent;
use App\Domain\Project\Exception\InvalidProjectStatusTransitionException;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectStatus;
use App\Domain\Project\ValueObject\ProjectType;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    private function makeProject(): Project
    {
        return Project::create(ProjectId::generate(), 'Test Project', ClientId::generate(), ProjectType::FORFAIT);
    }

    public function testCreateInitializesDefaults(): void
    {
        $project = $this->makeProject();

        static::assertSame('Test Project', $project->getName());
        static::assertSame(ProjectStatus::DRAFT, $project->getStatus());
        static::assertSame(ProjectType::FORFAIT, $project->getProjectType());
        static::assertFalse($project->isInternal());
        static::assertNull($project->getDescription());
        static::assertNull($project->getBudget());
        static::assertNotNull($project->getCreatedAt());
    }

    public function testCreateRecordsProjectCreatedEvent(): void
    {
        $project = $this->makeProject();
        $events = $project->pullDomainEvents();

        static::assertCount(1, $events);
        static::assertInstanceOf(ProjectCreatedEvent::class, $events[0]);
    }

    public function testInternalProject(): void
    {
        $project = Project::create(
            ProjectId::generate(),
            'Internal',
            ClientId::generate(),
            ProjectType::REGIE,
            isInternal: true,
        );

        static::assertTrue($project->isInternal());
        static::assertTrue($project->isTimeAndMaterials());
    }

    public function testActivateFromDraft(): void
    {
        $project = $this->makeProject();
        $project->pullDomainEvents();

        $project->activate();
        static::assertSame(ProjectStatus::ACTIVE, $project->getStatus());
        static::assertTrue($project->isActive());

        $events = $project->pullDomainEvents();
        static::assertCount(1, $events);
        static::assertInstanceOf(ProjectStatusChangedEvent::class, $events[0]);
    }

    public function testCannotCompleteFromDraft(): void
    {
        $project = $this->makeProject();

        $this->expectException(InvalidProjectStatusTransitionException::class);
        $project->complete();
    }

    public function testCompletedProjectIsClosedAndCannotReactivate(): void
    {
        $project = $this->makeProject();
        $project->activate();
        $project->complete();

        static::assertTrue($project->isClosed());
        static::assertSame(ProjectStatus::COMPLETED, $project->getStatus());
        static::assertNotNull($project->getCompletedAt());

        $this->expectException(InvalidProjectStatusTransitionException::class);
        $project->activate();
    }

    public function testPutOnHoldFromActive(): void
    {
        $project = $this->makeProject();
        $project->activate();
        $project->putOnHold();

        static::assertSame(ProjectStatus::ON_HOLD, $project->getStatus());
    }

    public function testCancelFromActive(): void
    {
        $project = $this->makeProject();
        $project->activate();
        $project->cancel();

        static::assertTrue($project->isClosed());
        static::assertSame(ProjectStatus::CANCELLED, $project->getStatus());
    }

    public function testChangeStatusNoOpIfSame(): void
    {
        $project = $this->makeProject();
        $project->pullDomainEvents();

        $project->changeStatus(ProjectStatus::DRAFT);

        static::assertSame([], $project->pullDomainEvents());
    }

    public function testUpdateDetails(): void
    {
        $project = $this->makeProject();
        $project->updateDetails('New Name', 'New description', 'PROJ-123');

        static::assertSame('New Name', $project->getName());
        static::assertSame('New description', $project->getDescription());
        static::assertSame('PROJ-123', $project->getReference());
        static::assertNotNull($project->getUpdatedAt());
    }

    public function testSetDatesValid(): void
    {
        $project = $this->makeProject();
        $start = new DateTimeImmutable('2026-01-01');
        $end = new DateTimeImmutable('2026-12-31');

        $project->setDates($start, $end);

        static::assertSame($start, $project->getStartDate());
        static::assertSame($end, $project->getEndDate());
    }

    public function testSetDatesStartAfterEndRejected(): void
    {
        $project = $this->makeProject();

        $this->expectException(InvalidArgumentException::class);
        $project->setDates(new DateTimeImmutable('2026-12-31'), new DateTimeImmutable('2026-01-01'));
    }

    public function testSetBudgetAndSoldAmount(): void
    {
        $project = $this->makeProject();
        $project->setBudget(Money::fromAmount(50_000));
        $project->setSoldAmount(Money::fromAmount(45_000));

        static::assertSame(50_000.0, $project->getBudget()->getAmount());
        static::assertSame(45_000.0, $project->getSoldAmount()->getAmount());
    }

    // TEST-COVERAGE-009 (sprint-019) — coverage extensions

    public function testSetBudgetToNullClears(): void
    {
        $project = $this->makeProject();
        $project->setBudget(Money::fromAmount(10_000));
        $project->setBudget(null);

        static::assertNull($project->getBudget());
        static::assertNotNull($project->getUpdatedAt());
    }

    public function testSetSoldAmountToNullClears(): void
    {
        $project = $this->makeProject();
        $project->setSoldAmount(Money::fromAmount(10_000));
        $project->setSoldAmount(null);

        static::assertNull($project->getSoldAmount());
    }

    public function testSetTechnicalInfo(): void
    {
        $project = $this->makeProject();
        $project->setTechnicalInfo('https://github.com/org/repo', 'https://docs.example.org');

        static::assertSame('https://github.com/org/repo', $project->getRepositoryUrl());
        static::assertSame('https://docs.example.org', $project->getDocumentationUrl());
        static::assertNotNull($project->getUpdatedAt());
    }

    public function testSetTechnicalInfoNullsClears(): void
    {
        $project = $this->makeProject();
        $project->setTechnicalInfo('https://github.com/org/repo', 'https://docs.example.org');
        $project->setTechnicalInfo(null, null);

        static::assertNull($project->getRepositoryUrl());
        static::assertNull($project->getDocumentationUrl());
    }

    public function testAddNotes(): void
    {
        $project = $this->makeProject();
        $project->addNotes('Important client feedback');

        static::assertSame('Important client feedback', $project->getNotes());
        static::assertNotNull($project->getUpdatedAt());
    }

    public function testIsFixedPriceForForfait(): void
    {
        $forfait = Project::create(
            ProjectId::generate(),
            'Forfait',
            ClientId::generate(),
            ProjectType::FORFAIT,
        );

        static::assertTrue($forfait->isFixedPrice());
        static::assertFalse($forfait->isTimeAndMaterials());
    }

    public function testIsTimeAndMaterialsForRegie(): void
    {
        $regie = Project::create(
            ProjectId::generate(),
            'Regie',
            ClientId::generate(),
            ProjectType::REGIE,
        );

        static::assertTrue($regie->isTimeAndMaterials());
        static::assertFalse($regie->isFixedPrice());
    }

    public function testGetDurationDaysNullWhenNoStartDate(): void
    {
        $project = $this->makeProject();
        $project->setDates(null, new DateTimeImmutable('2026-12-31'));

        static::assertNull($project->getDurationDays());
    }

    public function testGetDurationDaysNullWhenNoEndDate(): void
    {
        $project = $this->makeProject();
        $project->setDates(new DateTimeImmutable('2026-01-01'), null);

        static::assertNull($project->getDurationDays());
    }

    public function testGetDurationDaysCalculatesFromRange(): void
    {
        $project = $this->makeProject();
        $project->setDates(
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-31'),
        );

        static::assertSame(30, $project->getDurationDays());
    }

    public function testCancelFromDraftDirectly(): void
    {
        $project = $this->makeProject();
        $project->cancel();

        static::assertSame(ProjectStatus::CANCELLED, $project->getStatus());
        static::assertTrue($project->isClosed());
    }

    public function testIsClosedForCancelled(): void
    {
        $project = $this->makeProject();
        $project->cancel();

        static::assertTrue($project->isClosed());
        static::assertFalse($project->isActive());
    }

    public function testCompletedSetsCompletedAtTimestamp(): void
    {
        $project = $this->makeProject();
        $project->activate();
        $project->complete();

        static::assertNotNull($project->getCompletedAt());
        static::assertInstanceOf(DateTimeImmutable::class, $project->getCompletedAt());
    }

    public function testCancelledDoesNotSetCompletedAt(): void
    {
        $project = $this->makeProject();
        $project->cancel();

        static::assertNull($project->getCompletedAt());
    }

    public function testActivateRecordsStatusChangedEvent(): void
    {
        $project = $this->makeProject();
        $project->pullDomainEvents(); // drain create event
        $project->activate();

        $events = $project->pullDomainEvents();
        static::assertCount(1, $events);
        static::assertInstanceOf(ProjectStatusChangedEvent::class, $events[0]);
    }

    public function testCompleteRecordsStatusChangedEvent(): void
    {
        $project = $this->makeProject();
        $project->activate();
        $project->pullDomainEvents();

        $project->complete();

        $events = $project->pullDomainEvents();
        static::assertCount(1, $events);
        static::assertInstanceOf(ProjectStatusChangedEvent::class, $events[0]);
    }

    public function testInvalidTransitionDraftToOnHold(): void
    {
        $project = $this->makeProject();

        $this->expectException(InvalidProjectStatusTransitionException::class);
        $project->putOnHold();
    }

    public function testReconstituteDoesNotRecordEvents(): void
    {
        $project = Project::reconstitute(
            ProjectId::generate(),
            'Reconstituted',
            ClientId::generate(),
            ProjectType::FORFAIT,
            isInternal: false,
            extra: [
                'status' => ProjectStatus::ACTIVE,
                'description' => 'desc',
                'reference' => 'PROJ-2026-001',
                'budget' => Money::fromAmount(10_000),
                'soldAmount' => Money::fromAmount(9000),
            ],
        );

        static::assertSame([], $project->pullDomainEvents());
        static::assertSame(ProjectStatus::ACTIVE, $project->getStatus());
        static::assertSame('desc', $project->getDescription());
        static::assertSame('PROJ-2026-001', $project->getReference());
        static::assertSame(10_000.0, $project->getBudget()->getAmount());
    }

    public function testReconstituteFallbacksToDefaults(): void
    {
        $project = Project::reconstitute(
            ProjectId::generate(),
            'Minimal',
            ClientId::generate(),
            ProjectType::REGIE,
            isInternal: true,
        );

        static::assertSame(ProjectStatus::DRAFT, $project->getStatus());
        static::assertNull($project->getDescription());
        static::assertNull($project->getBudget());
        static::assertNull($project->getReference());
        static::assertSame([], $project->pullDomainEvents());
    }

    public function testUpdateDetailsClearsOptionalFields(): void
    {
        $project = $this->makeProject();
        $project->updateDetails('New name', 'desc', 'REF-1');
        $project->updateDetails('Cleaned', null, null);

        static::assertSame('Cleaned', $project->getName());
        static::assertNull($project->getDescription());
        static::assertNull($project->getReference());
    }

    public function testInternalFlagDefaultFalse(): void
    {
        $project = $this->makeProject();
        static::assertFalse($project->isInternal());
    }
}

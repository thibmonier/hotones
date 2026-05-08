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

        $this->assertSame('Test Project', $project->getName());
        $this->assertSame(ProjectStatus::DRAFT, $project->getStatus());
        $this->assertSame(ProjectType::FORFAIT, $project->getProjectType());
        $this->assertFalse($project->isInternal());
        $this->assertNull($project->getDescription());
        $this->assertNull($project->getBudget());
        $this->assertNotNull($project->getCreatedAt());
    }

    public function testCreateRecordsProjectCreatedEvent(): void
    {
        $project = $this->makeProject();
        $events = $project->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProjectCreatedEvent::class, $events[0]);
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

        $this->assertTrue($project->isInternal());
        $this->assertTrue($project->isTimeAndMaterials());
    }

    public function testActivateFromDraft(): void
    {
        $project = $this->makeProject();
        $project->pullDomainEvents();

        $project->activate();
        $this->assertSame(ProjectStatus::ACTIVE, $project->getStatus());
        $this->assertTrue($project->isActive());

        $events = $project->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProjectStatusChangedEvent::class, $events[0]);
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

        $this->assertTrue($project->isClosed());
        $this->assertSame(ProjectStatus::COMPLETED, $project->getStatus());
        $this->assertNotNull($project->getCompletedAt());

        $this->expectException(InvalidProjectStatusTransitionException::class);
        $project->activate();
    }

    public function testPutOnHoldFromActive(): void
    {
        $project = $this->makeProject();
        $project->activate();
        $project->putOnHold();

        $this->assertSame(ProjectStatus::ON_HOLD, $project->getStatus());
    }

    public function testCancelFromActive(): void
    {
        $project = $this->makeProject();
        $project->activate();
        $project->cancel();

        $this->assertTrue($project->isClosed());
        $this->assertSame(ProjectStatus::CANCELLED, $project->getStatus());
    }

    public function testChangeStatusNoOpIfSame(): void
    {
        $project = $this->makeProject();
        $project->pullDomainEvents();

        $project->changeStatus(ProjectStatus::DRAFT);

        $this->assertSame([], $project->pullDomainEvents());
    }

    public function testUpdateDetails(): void
    {
        $project = $this->makeProject();
        $project->updateDetails('New Name', 'New description', 'PROJ-123');

        $this->assertSame('New Name', $project->getName());
        $this->assertSame('New description', $project->getDescription());
        $this->assertSame('PROJ-123', $project->getReference());
        $this->assertNotNull($project->getUpdatedAt());
    }

    public function testSetDatesValid(): void
    {
        $project = $this->makeProject();
        $start = new DateTimeImmutable('2026-01-01');
        $end = new DateTimeImmutable('2026-12-31');

        $project->setDates($start, $end);

        $this->assertSame($start, $project->getStartDate());
        $this->assertSame($end, $project->getEndDate());
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
        $project->setBudget(Money::fromAmount(50000));
        $project->setSoldAmount(Money::fromAmount(45000));

        $this->assertSame(50000.0, $project->getBudget()->getAmount());
        $this->assertSame(45000.0, $project->getSoldAmount()->getAmount());
    }

    // TEST-COVERAGE-009 (sprint-019) — coverage extensions

    public function testSetBudgetToNullClears(): void
    {
        $project = $this->makeProject();
        $project->setBudget(Money::fromAmount(10000));
        $project->setBudget(null);

        $this->assertNull($project->getBudget());
        $this->assertNotNull($project->getUpdatedAt());
    }

    public function testSetSoldAmountToNullClears(): void
    {
        $project = $this->makeProject();
        $project->setSoldAmount(Money::fromAmount(10000));
        $project->setSoldAmount(null);

        $this->assertNull($project->getSoldAmount());
    }

    public function testSetTechnicalInfo(): void
    {
        $project = $this->makeProject();
        $project->setTechnicalInfo('https://github.com/org/repo', 'https://docs.example.org');

        $this->assertSame('https://github.com/org/repo', $project->getRepositoryUrl());
        $this->assertSame('https://docs.example.org', $project->getDocumentationUrl());
        $this->assertNotNull($project->getUpdatedAt());
    }

    public function testSetTechnicalInfoNullsClears(): void
    {
        $project = $this->makeProject();
        $project->setTechnicalInfo('https://github.com/org/repo', 'https://docs.example.org');
        $project->setTechnicalInfo(null, null);

        $this->assertNull($project->getRepositoryUrl());
        $this->assertNull($project->getDocumentationUrl());
    }

    public function testAddNotes(): void
    {
        $project = $this->makeProject();
        $project->addNotes('Important client feedback');

        $this->assertSame('Important client feedback', $project->getNotes());
        $this->assertNotNull($project->getUpdatedAt());
    }

    public function testIsFixedPriceForForfait(): void
    {
        $forfait = Project::create(
            ProjectId::generate(),
            'Forfait',
            ClientId::generate(),
            ProjectType::FORFAIT,
        );

        $this->assertTrue($forfait->isFixedPrice());
        $this->assertFalse($forfait->isTimeAndMaterials());
    }

    public function testIsTimeAndMaterialsForRegie(): void
    {
        $regie = Project::create(
            ProjectId::generate(),
            'Regie',
            ClientId::generate(),
            ProjectType::REGIE,
        );

        $this->assertTrue($regie->isTimeAndMaterials());
        $this->assertFalse($regie->isFixedPrice());
    }

    public function testGetDurationDaysNullWhenNoStartDate(): void
    {
        $project = $this->makeProject();
        $project->setDates(null, new DateTimeImmutable('2026-12-31'));

        $this->assertNull($project->getDurationDays());
    }

    public function testGetDurationDaysNullWhenNoEndDate(): void
    {
        $project = $this->makeProject();
        $project->setDates(new DateTimeImmutable('2026-01-01'), null);

        $this->assertNull($project->getDurationDays());
    }

    public function testGetDurationDaysCalculatesFromRange(): void
    {
        $project = $this->makeProject();
        $project->setDates(
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-31'),
        );

        $this->assertSame(30, $project->getDurationDays());
    }

    public function testCancelFromDraftDirectly(): void
    {
        $project = $this->makeProject();
        $project->cancel();

        $this->assertSame(ProjectStatus::CANCELLED, $project->getStatus());
        $this->assertTrue($project->isClosed());
    }

    public function testIsClosedForCancelled(): void
    {
        $project = $this->makeProject();
        $project->cancel();

        $this->assertTrue($project->isClosed());
        $this->assertFalse($project->isActive());
    }

    public function testCompletedSetsCompletedAtTimestamp(): void
    {
        $project = $this->makeProject();
        $project->activate();
        $project->complete();

        $this->assertNotNull($project->getCompletedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $project->getCompletedAt());
    }

    public function testCancelledDoesNotSetCompletedAt(): void
    {
        $project = $this->makeProject();
        $project->cancel();

        $this->assertNull($project->getCompletedAt());
    }

    public function testActivateRecordsStatusChangedEvent(): void
    {
        $project = $this->makeProject();
        $project->pullDomainEvents(); // drain create event
        $project->activate();

        $events = $project->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProjectStatusChangedEvent::class, $events[0]);
    }

    public function testCompleteRecordsStatusChangedEvent(): void
    {
        $project = $this->makeProject();
        $project->activate();
        $project->pullDomainEvents();

        $project->complete();

        $events = $project->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProjectStatusChangedEvent::class, $events[0]);
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
                'budget' => Money::fromAmount(10000),
                'soldAmount' => Money::fromAmount(9000),
            ],
        );

        $this->assertSame([], $project->pullDomainEvents());
        $this->assertSame(ProjectStatus::ACTIVE, $project->getStatus());
        $this->assertSame('desc', $project->getDescription());
        $this->assertSame('PROJ-2026-001', $project->getReference());
        $this->assertSame(10000.0, $project->getBudget()->getAmount());
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

        $this->assertSame(ProjectStatus::DRAFT, $project->getStatus());
        $this->assertNull($project->getDescription());
        $this->assertNull($project->getBudget());
        $this->assertNull($project->getReference());
        $this->assertSame([], $project->pullDomainEvents());
    }

    public function testUpdateDetailsClearsOptionalFields(): void
    {
        $project = $this->makeProject();
        $project->updateDetails('New name', 'desc', 'REF-1');
        $project->updateDetails('Cleaned', null, null);

        $this->assertSame('Cleaned', $project->getName());
        $this->assertNull($project->getDescription());
        $this->assertNull($project->getReference());
    }

    public function testInternalFlagDefaultFalse(): void
    {
        $project = $this->makeProject();
        $this->assertFalse($project->isInternal());
    }
}

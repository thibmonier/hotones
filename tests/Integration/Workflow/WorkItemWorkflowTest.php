<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workflow;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\WorkItem\Entity\WorkItem;
use App\Domain\WorkItem\ValueObject\HourlyRate;
use App\Domain\WorkItem\ValueObject\WorkedHours;
use App\Domain\WorkItem\ValueObject\WorkItemId;
use App\Domain\WorkItem\ValueObject\WorkItemStatus;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\Registry;

/**
 * Sprint-022 sub-epic F WORKFLOW-YAML — vérifie intégration Symfony
 * Workflow component avec WorkItem aggregate Domain pure (sprint-021
 * US-101).
 *
 * ADR-0016 Q3.1 4 états + sprint-021 retro S-3 (Symfony Workflow optionnel
 * pour UI/listeners visuels).
 *
 * Defense-in-depth :
 * - Domain `WorkItem::markAsXxx()` valide via `WorkItemStatus::canTransitionTo`
 * - Symfony Workflow `can()/apply()` valide via state machine config
 * Les deux mécanismes doivent rester cohérents.
 */
final class WorkItemWorkflowTest extends KernelTestCase
{
    private Registry $registry;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->registry = static::getContainer()->get('workflow.registry');
    }

    public function testWorkflowRegistryFindsWorkItemDefinition(): void
    {
        $workItem = $this->makeDraft();
        $workflow = $this->registry->get($workItem);

        self::assertSame('work_item', $workflow->getName());
    }

    public function testCanValidateFromDraft(): void
    {
        $workItem = $this->makeDraft();
        $workflow = $this->registry->get($workItem);

        self::assertTrue($workflow->can($workItem, 'validate'));
        self::assertFalse($workflow->can($workItem, 'bill'));
        self::assertFalse($workflow->can($workItem, 'mark_paid'));
    }

    public function testCanBillFromValidated(): void
    {
        $workItem = $this->makeDraft();
        $workItem->markAsValidated();

        $workflow = $this->registry->get($workItem);

        self::assertTrue($workflow->can($workItem, 'bill'));
        self::assertFalse($workflow->can($workItem, 'validate')); // no self-loop
        self::assertFalse($workflow->can($workItem, 'mark_paid'));
    }

    public function testCannotBillFromDraft(): void
    {
        $workItem = $this->makeDraft();
        $workflow = $this->registry->get($workItem);

        self::assertFalse($workflow->can($workItem, 'bill'));
    }

    public function testWorkflowDefinitionMatchesDomainState(): void
    {
        $workItem = $this->makeDraft();
        $workflow = $this->registry->get($workItem);
        $definition = $workflow->getDefinition();

        $places = $definition->getPlaces();
        self::assertContains('draft', $places);
        self::assertContains('validated', $places);
        self::assertContains('billed', $places);
        self::assertContains('paid', $places);

        $transitions = array_map(static fn ($t) => $t->getName(), $definition->getTransitions());
        self::assertContains('validate', $transitions);
        self::assertContains('bill', $transitions);
        self::assertContains('mark_paid', $transitions);
    }

    public function testInitialMarkingIsDraft(): void
    {
        $workItem = $this->makeDraft();

        // Domain : marking via getStatus() (BackedEnum)
        self::assertSame(WorkItemStatus::DRAFT, $workItem->getStatus());
        self::assertSame('draft', $workItem->getStatus()->value);
    }

    private function makeDraft(): WorkItem
    {
        return WorkItem::create(
            id: WorkItemId::generate(),
            projectId: ProjectId::generate(),
            contributorId: ContributorId::fromLegacyInt(42),
            workedOn: new DateTimeImmutable('2026-05-12'),
            hours: WorkedHours::fromFloat(7.0),
            costRate: HourlyRate::fromAmount(50.0),
            billedRate: HourlyRate::fromAmount(100.0),
        );
    }
}

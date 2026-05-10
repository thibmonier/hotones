<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\ValueObject;

use App\Domain\WorkItem\ValueObject\WorkItemStatus;
use PHPUnit\Framework\TestCase;

final class WorkItemStatusTest extends TestCase
{
    public function testValuesArePersistableStrings(): void
    {
        self::assertSame('draft', WorkItemStatus::DRAFT->value);
        self::assertSame('validated', WorkItemStatus::VALIDATED->value);
        self::assertSame('billed', WorkItemStatus::BILLED->value);
        self::assertSame('paid', WorkItemStatus::PAID->value);
    }

    public function testIsHelpers(): void
    {
        self::assertTrue(WorkItemStatus::DRAFT->isDraft());
        self::assertFalse(WorkItemStatus::VALIDATED->isDraft());
        self::assertTrue(WorkItemStatus::VALIDATED->isValidated());
        self::assertTrue(WorkItemStatus::BILLED->isBilled());
        self::assertTrue(WorkItemStatus::PAID->isPaid());
    }

    public function testCanTransitionDraftToValidated(): void
    {
        self::assertTrue(WorkItemStatus::DRAFT->canTransitionTo(WorkItemStatus::VALIDATED));
    }

    public function testCanTransitionValidatedToBilled(): void
    {
        self::assertTrue(WorkItemStatus::VALIDATED->canTransitionTo(WorkItemStatus::BILLED));
    }

    public function testCanTransitionBilledToPaid(): void
    {
        self::assertTrue(WorkItemStatus::BILLED->canTransitionTo(WorkItemStatus::PAID));
    }

    public function testCannotTransitionDraftToBilled(): void
    {
        self::assertFalse(WorkItemStatus::DRAFT->canTransitionTo(WorkItemStatus::BILLED));
    }

    public function testCannotTransitionValidatedToDraft(): void
    {
        self::assertFalse(WorkItemStatus::VALIDATED->canTransitionTo(WorkItemStatus::DRAFT));
    }

    public function testCannotTransitionPaidToBilled(): void
    {
        self::assertFalse(WorkItemStatus::PAID->canTransitionTo(WorkItemStatus::BILLED));
    }

    public function testCannotTransitionToSameState(): void
    {
        self::assertFalse(WorkItemStatus::DRAFT->canTransitionTo(WorkItemStatus::DRAFT));
        self::assertFalse(WorkItemStatus::VALIDATED->canTransitionTo(WorkItemStatus::VALIDATED));
    }
}

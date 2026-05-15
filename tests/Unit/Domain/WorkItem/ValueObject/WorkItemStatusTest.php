<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkItem\ValueObject;

use App\Domain\WorkItem\ValueObject\WorkItemStatus;
use PHPUnit\Framework\TestCase;

final class WorkItemStatusTest extends TestCase
{
    public function testValuesArePersistableStrings(): void
    {
        static::assertSame('draft', WorkItemStatus::DRAFT->value);
        static::assertSame('validated', WorkItemStatus::VALIDATED->value);
        static::assertSame('billed', WorkItemStatus::BILLED->value);
        static::assertSame('paid', WorkItemStatus::PAID->value);
    }

    public function testIsHelpers(): void
    {
        static::assertTrue(WorkItemStatus::DRAFT->isDraft());
        static::assertFalse(WorkItemStatus::VALIDATED->isDraft());
        static::assertTrue(WorkItemStatus::VALIDATED->isValidated());
        static::assertTrue(WorkItemStatus::BILLED->isBilled());
        static::assertTrue(WorkItemStatus::PAID->isPaid());
    }

    public function testCanTransitionDraftToValidated(): void
    {
        static::assertTrue(WorkItemStatus::DRAFT->canTransitionTo(WorkItemStatus::VALIDATED));
    }

    public function testCanTransitionValidatedToBilled(): void
    {
        static::assertTrue(WorkItemStatus::VALIDATED->canTransitionTo(WorkItemStatus::BILLED));
    }

    public function testCanTransitionBilledToPaid(): void
    {
        static::assertTrue(WorkItemStatus::BILLED->canTransitionTo(WorkItemStatus::PAID));
    }

    public function testCannotTransitionDraftToBilled(): void
    {
        static::assertFalse(WorkItemStatus::DRAFT->canTransitionTo(WorkItemStatus::BILLED));
    }

    public function testCannotTransitionValidatedToDraft(): void
    {
        static::assertFalse(WorkItemStatus::VALIDATED->canTransitionTo(WorkItemStatus::DRAFT));
    }

    public function testCannotTransitionPaidToBilled(): void
    {
        static::assertFalse(WorkItemStatus::PAID->canTransitionTo(WorkItemStatus::BILLED));
    }

    public function testCannotTransitionToSameState(): void
    {
        static::assertFalse(WorkItemStatus::DRAFT->canTransitionTo(WorkItemStatus::DRAFT));
        static::assertFalse(WorkItemStatus::VALIDATED->canTransitionTo(WorkItemStatus::VALIDATED));
    }
}

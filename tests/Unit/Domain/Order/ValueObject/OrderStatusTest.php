<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\ValueObject;

use App\Domain\Order\ValueObject\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    public function testEightCases(): void
    {
        static::assertCount(8, OrderStatus::cases());
    }

    public function testActiveStatuses(): void
    {
        static::assertTrue(OrderStatus::DRAFT->isActive());
        static::assertTrue(OrderStatus::TO_SIGN->isActive());
        static::assertTrue(OrderStatus::WON->isActive());
        static::assertTrue(OrderStatus::SIGNED->isActive());
        static::assertTrue(OrderStatus::STANDBY->isActive());
        static::assertFalse(OrderStatus::LOST->isActive());
        static::assertFalse(OrderStatus::COMPLETED->isActive());
        static::assertFalse(OrderStatus::ABANDONED->isActive());
    }

    public function testClosedStatuses(): void
    {
        static::assertTrue(OrderStatus::LOST->isClosed());
        static::assertTrue(OrderStatus::COMPLETED->isClosed());
        static::assertTrue(OrderStatus::ABANDONED->isClosed());
        static::assertFalse(OrderStatus::DRAFT->isClosed());
        static::assertFalse(OrderStatus::SIGNED->isClosed());
    }

    public function testTransitionsFromDraft(): void
    {
        static::assertTrue(OrderStatus::DRAFT->canTransitionTo(OrderStatus::TO_SIGN));
        static::assertTrue(OrderStatus::DRAFT->canTransitionTo(OrderStatus::ABANDONED));
        static::assertFalse(OrderStatus::DRAFT->canTransitionTo(OrderStatus::WON));
        static::assertFalse(OrderStatus::DRAFT->canTransitionTo(OrderStatus::SIGNED));
    }

    public function testTransitionsFromToSign(): void
    {
        static::assertTrue(OrderStatus::TO_SIGN->canTransitionTo(OrderStatus::WON));
        static::assertTrue(OrderStatus::TO_SIGN->canTransitionTo(OrderStatus::LOST));
        static::assertTrue(OrderStatus::TO_SIGN->canTransitionTo(OrderStatus::STANDBY));
        static::assertFalse(OrderStatus::TO_SIGN->canTransitionTo(OrderStatus::SIGNED));
    }

    public function testClosedStatusesAreTerminal(): void
    {
        foreach ([OrderStatus::LOST, OrderStatus::COMPLETED, OrderStatus::ABANDONED] as $status) {
            foreach (OrderStatus::cases() as $target) {
                static::assertFalse(
                    $status->canTransitionTo($target),
                    "{$status->name} should not transition to {$target->name}",
                );
            }
        }
    }

    public function testGetLabel(): void
    {
        static::assertSame('Brouillon', OrderStatus::DRAFT->getLabel());
        static::assertSame('Signé', OrderStatus::SIGNED->getLabel());
        static::assertSame('Terminé', OrderStatus::COMPLETED->getLabel());
    }
}

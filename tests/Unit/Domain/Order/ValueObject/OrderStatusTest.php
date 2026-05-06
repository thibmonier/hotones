<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\ValueObject;

use App\Domain\Order\ValueObject\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    public function testEightCases(): void
    {
        $this->assertCount(8, OrderStatus::cases());
    }

    public function testActiveStatuses(): void
    {
        $this->assertTrue(OrderStatus::DRAFT->isActive());
        $this->assertTrue(OrderStatus::TO_SIGN->isActive());
        $this->assertTrue(OrderStatus::WON->isActive());
        $this->assertTrue(OrderStatus::SIGNED->isActive());
        $this->assertTrue(OrderStatus::STANDBY->isActive());
        $this->assertFalse(OrderStatus::LOST->isActive());
        $this->assertFalse(OrderStatus::COMPLETED->isActive());
        $this->assertFalse(OrderStatus::ABANDONED->isActive());
    }

    public function testClosedStatuses(): void
    {
        $this->assertTrue(OrderStatus::LOST->isClosed());
        $this->assertTrue(OrderStatus::COMPLETED->isClosed());
        $this->assertTrue(OrderStatus::ABANDONED->isClosed());
        $this->assertFalse(OrderStatus::DRAFT->isClosed());
        $this->assertFalse(OrderStatus::SIGNED->isClosed());
    }

    public function testTransitionsFromDraft(): void
    {
        $this->assertTrue(OrderStatus::DRAFT->canTransitionTo(OrderStatus::TO_SIGN));
        $this->assertTrue(OrderStatus::DRAFT->canTransitionTo(OrderStatus::ABANDONED));
        $this->assertFalse(OrderStatus::DRAFT->canTransitionTo(OrderStatus::WON));
        $this->assertFalse(OrderStatus::DRAFT->canTransitionTo(OrderStatus::SIGNED));
    }

    public function testTransitionsFromToSign(): void
    {
        $this->assertTrue(OrderStatus::TO_SIGN->canTransitionTo(OrderStatus::WON));
        $this->assertTrue(OrderStatus::TO_SIGN->canTransitionTo(OrderStatus::LOST));
        $this->assertTrue(OrderStatus::TO_SIGN->canTransitionTo(OrderStatus::STANDBY));
        $this->assertFalse(OrderStatus::TO_SIGN->canTransitionTo(OrderStatus::SIGNED));
    }

    public function testClosedStatusesAreTerminal(): void
    {
        foreach ([OrderStatus::LOST, OrderStatus::COMPLETED, OrderStatus::ABANDONED] as $status) {
            foreach (OrderStatus::cases() as $target) {
                $this->assertFalse(
                    $status->canTransitionTo($target),
                    "$status->name should not transition to $target->name",
                );
            }
        }
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Brouillon', OrderStatus::DRAFT->getLabel());
        $this->assertSame('Signé', OrderStatus::SIGNED->getLabel());
        $this->assertSame('Terminé', OrderStatus::COMPLETED->getLabel());
    }
}

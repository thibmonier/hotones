<?php

declare(strict_types=1);

namespace App\Domain\Order\Event;

use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Shared\Interface\DomainEventInterface;

final readonly class OrderStatusChangedEvent implements DomainEventInterface
{
    public function __construct(
        private OrderId $orderId,
        private OrderStatus $previousStatus,
        private OrderStatus $newStatus,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public static function create(
        OrderId $orderId,
        OrderStatus $previousStatus,
        OrderStatus $newStatus,
    ): self {
        return new self($orderId, $previousStatus, $newStatus, new \DateTimeImmutable());
    }

    public function getOrderId(): OrderId
    {
        return $this->orderId;
    }

    public function getPreviousStatus(): OrderStatus
    {
        return $this->previousStatus;
    }

    public function getNewStatus(): OrderStatus
    {
        return $this->newStatus;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Order\Event;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Shared\Interface\DomainEventInterface;

final readonly class OrderCreatedEvent implements DomainEventInterface
{
    public function __construct(
        private OrderId $orderId,
        private ClientId $clientId,
        private string $reference,
        private \DateTimeImmutable $occurredOn,
    ) {
    }

    public static function create(OrderId $orderId, ClientId $clientId, string $reference): self
    {
        return new self($orderId, $clientId, $reference, new \DateTimeImmutable());
    }

    public function getOrderId(): OrderId
    {
        return $this->orderId;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}

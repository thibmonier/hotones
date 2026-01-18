<?php

declare(strict_types=1);

namespace App\Domain\Order\Repository;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\Exception\OrderNotFoundException;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderStatus;

/**
 * Repository interface for Order aggregate root.
 *
 * Implementations should be in Infrastructure layer.
 */
interface OrderRepositoryInterface
{
    /**
     * Find an order by its ID.
     *
     * @throws OrderNotFoundException if the order does not exist
     */
    public function findById(OrderId $id): Order;

    /**
     * Find an order by its ID, returning null if not found.
     */
    public function findByIdOrNull(OrderId $id): ?Order;

    /**
     * Find an order by its reference.
     */
    public function findByReference(string $reference): ?Order;

    /**
     * Find all orders for a specific client.
     *
     * @return Order[]
     */
    public function findByClientId(ClientId $clientId): array;

    /**
     * Find all orders with a specific status.
     *
     * @return Order[]
     */
    public function findByStatus(OrderStatus $status): array;

    /**
     * Find all active orders (not closed).
     *
     * @return Order[]
     */
    public function findActive(): array;

    /**
     * Find all orders.
     *
     * @return Order[]
     */
    public function findAll(): array;

    /**
     * Persist an order.
     */
    public function save(Order $order): void;

    /**
     * Remove an order.
     */
    public function delete(Order $order): void;

    /**
     * Generate a new unique reference.
     */
    public function nextReference(): string;
}

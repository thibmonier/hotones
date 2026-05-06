<?php

declare(strict_types=1);

namespace App\Infrastructure\Order\Persistence\Doctrine;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Order\Entity\Order as DddOrder;
use App\Domain\Order\Exception\OrderNotFoundException;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Entity\Order as FlatOrder;
use App\Infrastructure\Order\Translator\OrderDddToFlatTranslator;
use App\Infrastructure\Order\Translator\OrderFlatToDddTranslator;
use App\Repository\OrderRepository as FlatOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/**
 * Anti-Corruption Layer adapter — implements DDD `OrderRepositoryInterface`.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 * @see ADR-0007 Order BC coexistence
 */
final readonly class DoctrineDddOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private FlatOrderRepository $flatRepository,
        private EntityManagerInterface $entityManager,
        private OrderFlatToDddTranslator $flatToDdd,
        private OrderDddToFlatTranslator $dddToFlat,
    ) {
    }

    public function findById(OrderId $id): DddOrder
    {
        $order = $this->findByIdOrNull($id);
        if ($order === null) {
            throw new OrderNotFoundException(sprintf('Order %s not found', (string) $id));
        }

        return $order;
    }

    public function findByIdOrNull(OrderId $id): ?DddOrder
    {
        if (!$id->isLegacy()) {
            return null;
        }

        $flat = $this->flatRepository->find($id->toLegacyInt());

        return $flat !== null ? $this->flatToDdd->translate($flat) : null;
    }

    public function findByReference(string $reference): ?DddOrder
    {
        $flat = $this->flatRepository->findOneBy(['orderNumber' => $reference]);

        return $flat !== null ? $this->flatToDdd->translate($flat) : null;
    }

    /**
     * @return array<DddOrder>
     */
    public function findByClientId(ClientId $clientId): array
    {
        // Phase 2: Order doesn't link directly to Client; would require JOIN
        // via Project. Out of scope for ACL Phase 2.
        return [];
    }

    /**
     * @return array<DddOrder>
     */
    public function findByStatus(OrderStatus $status): array
    {
        $flatStatus = match ($status) {
            OrderStatus::DRAFT, OrderStatus::TO_SIGN => 'a_signer',
            OrderStatus::WON => 'gagne',
            OrderStatus::SIGNED => 'signe',
            OrderStatus::LOST => 'perdu',
            OrderStatus::COMPLETED => 'termine',
            OrderStatus::STANDBY => 'standby',
            OrderStatus::ABANDONED => 'abandonne',
        };

        return array_map(
            fn (FlatOrder $flat): DddOrder => $this->flatToDdd->translate($flat),
            $this->flatRepository->findBy(['status' => $flatStatus]),
        );
    }

    /**
     * @return array<DddOrder>
     */
    public function findActive(): array
    {
        // Active = not in terminal states
        $terminalStates = ['perdu', 'termine', 'abandonne'];

        $allFlats = $this->flatRepository->findAll();
        $actives = array_filter(
            $allFlats,
            fn (FlatOrder $flat): bool => !in_array($flat->status, $terminalStates, true),
        );

        return array_map(
            fn (FlatOrder $flat): DddOrder => $this->flatToDdd->translate($flat),
            $actives,
        );
    }

    /**
     * @return array<DddOrder>
     */
    public function findAll(): array
    {
        return array_map(
            fn (FlatOrder $flat): DddOrder => $this->flatToDdd->translate($flat),
            $this->flatRepository->findAll(),
        );
    }

    public function save(DddOrder $order): void
    {
        $id = $order->getId();
        if (!$id->isLegacy()) {
            throw new RuntimeException('Saving DDD Order with pure UUID id not yet supported during Phase 2.');
        }

        $flat = $this->flatRepository->find($id->toLegacyInt())
            ?? throw new OrderNotFoundException(sprintf('Cannot update Order %s: not found', (string) $id));

        $this->dddToFlat->applyTo($order, $flat);

        $this->entityManager->persist($flat);
        $this->entityManager->flush();
    }

    public function delete(DddOrder $order): void
    {
        $id = $order->getId();
        if (!$id->isLegacy()) {
            throw new RuntimeException('Deleting DDD Order with pure UUID id not yet supported');
        }

        $flat = $this->flatRepository->find($id->toLegacyInt())
            ?? throw new OrderNotFoundException(sprintf('Cannot delete Order %s: not found', (string) $id));

        $this->entityManager->remove($flat);
        $this->entityManager->flush();
    }

    public function nextReference(): string
    {
        // Phase 2: delegate to flat naming logic. The flat layer auto-generates
        // orderNumber on persist. For DDD-side allocation, we'd need a separate
        // sequence table — out of scope.
        return 'D'.date('Ym').'-PENDING';
    }
}

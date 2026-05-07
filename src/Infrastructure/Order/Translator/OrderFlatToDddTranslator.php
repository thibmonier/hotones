<?php

declare(strict_types=1);

namespace App\Infrastructure\Order\Translator;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Order\Entity\Order as DddOrder;
use App\Domain\Order\ValueObject\ContractType;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Shared\ValueObject\Money;
use App\Entity\Order as FlatOrder;
use DateTimeImmutable;
use DateTimeInterface;

use const PHP_INT_MAX;

use RuntimeException;

/**
 * Anti-Corruption Layer translator (flat → DDD) for the Order BC.
 *
 * Stateless service.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 * @see ADR-0007 Order BC coexistence (status superset 8 vs 7)
 */
final class OrderFlatToDddTranslator
{
    public function translate(FlatOrder $flat): DddOrder
    {
        $id = OrderId::fromLegacyInt($flat->id ?? throw new RuntimeException('Cannot translate unsaved Order'));

        // Order flat doesn't have a direct ClientId reference — resolved via Project.client.
        // Phase 2 ACL fallback: PHP_INT_MAX placeholder when project/client unavailable.
        $clientId =
            $flat->project !== null && $flat->project->client !== null && $flat->project->client->id !== null
                ? ClientId::fromLegacyInt($flat->project->client->id)
                : ClientId::fromLegacyInt(PHP_INT_MAX);

        $contractType = $this->mapContractType($flat->contractType);
        $status = $this->mapStatus($flat->status);

        $amount = Money::fromAmount((float) ($flat->totalAmount ?? '0.00'));

        return DddOrder::reconstitute(
            id: $id,
            reference: $flat->orderNumber,
            clientId: $clientId,
            contractType: $contractType,
            amount: $amount,
            extra: [
                'status' => $status,
                'title' => $flat->name,
                'description' => $flat->description,
                'discount' => null, // Out of scope Phase 2 (computed via lines)
                'startDate' => null,
                'endDate' => null,
                'signedAt' => $flat->validatedAt instanceof DateTimeInterface
                    ? DateTimeImmutable::createFromInterface($flat->validatedAt)
                    : null,
                'notes' => $flat->notes,
                'createdAt' => $flat->getCreatedAt() instanceof DateTimeInterface
                    ? DateTimeImmutable::createFromInterface($flat->getCreatedAt())
                    : new DateTimeImmutable(),
            ],
        );
    }

    private function mapContractType(string $flatType): ContractType
    {
        return match ($flatType) {
            'forfait' => ContractType::FIXED_PRICE,
            'regie' => ContractType::TIME_AND_MATERIAL,
            default => ContractType::FIXED_PRICE,
        };
    }

    /**
     * Flat status values match DDD by string except DRAFT (DDD-only).
     */
    private function mapStatus(string $flatStatus): OrderStatus
    {
        return match ($flatStatus) {
            'a_signer' => OrderStatus::TO_SIGN,
            'gagne' => OrderStatus::WON,
            'signe' => OrderStatus::SIGNED,
            'perdu' => OrderStatus::LOST,
            'termine' => OrderStatus::COMPLETED,
            'standby' => OrderStatus::STANDBY,
            'abandonne' => OrderStatus::ABANDONED,
            default => OrderStatus::TO_SIGN,
        };
    }
}

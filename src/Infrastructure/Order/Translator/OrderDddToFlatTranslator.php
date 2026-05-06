<?php

declare(strict_types=1);

namespace App\Infrastructure\Order\Translator;

use App\Domain\Order\Entity\Order as DddOrder;
use App\Domain\Order\ValueObject\ContractType;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Entity\Order as FlatOrder;

/**
 * Anti-Corruption Layer translator (DDD → flat) for the Order BC.
 *
 * Stateless service.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 */
final class OrderDddToFlatTranslator
{
    public function applyTo(DddOrder $ddd, FlatOrder $flat): void
    {
        $flat->name = $ddd->getTitle();
        $flat->description = $ddd->getDescription();
        $flat->status = $this->mapStatus($ddd->getStatus());
        $flat->contractType = $this->mapContractType($ddd->getContractType());
        $flat->totalAmount = (string) $ddd->getAmount()->getAmount();
        $flat->notes = $ddd->getNotes();
        $flat->orderNumber = $ddd->getReference();
    }

    /**
     * DDD status (8 cases) → flat (7 cases) — DRAFT collapses to a_signer.
     */
    private function mapStatus(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::DRAFT,
            OrderStatus::TO_SIGN => 'a_signer',
            OrderStatus::WON => 'gagne',
            OrderStatus::SIGNED => 'signe',
            OrderStatus::LOST => 'perdu',
            OrderStatus::COMPLETED => 'termine',
            OrderStatus::STANDBY => 'standby',
            OrderStatus::ABANDONED => 'abandonne',
        };
    }

    private function mapContractType(ContractType $type): string
    {
        return match ($type) {
            ContractType::FIXED_PRICE => 'forfait',
            ContractType::TIME_AND_MATERIAL => 'regie',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Client\Translator;

use App\Domain\Client\Entity\Client as DddClient;
use App\Domain\Client\ValueObject\ServiceLevel;
use App\Entity\Client as FlatClient;
use App\Entity\Company;

/**
 * Anti-Corruption Layer translator (DDD → flat) for the Client BC.
 *
 * Used by `DoctrineDddClientRepository` on save() to update the legacy
 * `App\Entity\Client` from a DDD aggregate state.
 *
 * Stateless service.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 * @see ADR-0005 Client BC coexistence
 */
final class ClientDddToFlatTranslator
{
    /**
     * Update the given flat client with state from the DDD aggregate.
     *
     * Caller is responsible for persisting / flushing the entity manager.
     */
    public function applyTo(DddClient $ddd, FlatClient $flat, Company $company): void
    {
        $flat->company = $company;
        $flat->name = $ddd->getName()->getValue();
        $flat->serviceLevel = $this->mapServiceLevel($ddd->getServiceLevel());
        $flat->description = $ddd->getNotes();
    }

    /**
     * Reverse mapping DDD enum (STANDARD/PREMIUM/ENTERPRISE, 3 cases) →
     * flat string (vip/priority/standard/low, 4 cases). DDD is a coarser
     * grain so we collapse:
     *   ENTERPRISE → vip (highest tier)
     *   PREMIUM    → standard
     *   STANDARD   → low.
     *
     * NOTE: this is a lossy mapping — `priority` in flat is unreachable
     * via DDD writes. ADR-0005 documents this Phase 2 limitation.
     */
    private function mapServiceLevel(ServiceLevel $level): string
    {
        return match ($level) {
            ServiceLevel::ENTERPRISE => 'vip',
            ServiceLevel::PREMIUM => 'standard',
            ServiceLevel::STANDARD => 'low',
        };
    }
}

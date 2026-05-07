<?php

declare(strict_types=1);

namespace App\Infrastructure\Client\Translator;

use App\Domain\Client\Entity\Client as DddClient;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\ValueObject\CompanyName;
use App\Domain\Client\ValueObject\ServiceLevel;
use App\Domain\Shared\ValueObject\Email;
use App\Entity\Client as FlatClient;
use DateTimeImmutable;
use RuntimeException;

/**
 * Anti-Corruption Layer translator (flat → DDD) for the Client BC.
 *
 * Translates the legacy `App\Entity\Client` (Doctrine annotations, table
 * `clients`) into the DDD aggregate `App\Domain\Client\Entity\Client`.
 *
 * Stateless service.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 * @see ADR-0005 Client BC coexistence (ServiceLevel divergence)
 */
final class ClientFlatToDddTranslator
{
    public function translate(FlatClient $flat): DddClient
    {
        $id = ClientId::fromLegacyInt($flat->id ?? throw new RuntimeException('Cannot translate unsaved Client'));
        $name = CompanyName::fromString($flat->name);
        $serviceLevel = $this->mapServiceLevel($flat->serviceLevel);

        $email = null;
        // Flat entity has no top-level `email` field on Client itself —
        // contacts are tracked via ClientContact. Phase 2 ACL focuses on
        // basic profile only, contacts are out of scope.

        return DddClient::reconstitute(id: $id, name: $name, serviceLevel: $serviceLevel, extra: [
            'email' => $email,
            'phone' => null,
            'address' => null,
            'city' => null,
            'postalCode' => null,
            'country' => null,
            'vatNumber' => null,
            'isActive' => true, // Flat has no soft-delete, all visible clients are active
            'notes' => $flat->description,
            'createdAt' => new DateTimeImmutable(),
            'updatedAt' => null,
        ]);
    }

    /**
     * Map flat service level (vip/priority/standard/low) to DDD enum
     * (STANDARD/PREMIUM/ENTERPRISE) per ADR-0005.
     */
    private function mapServiceLevel(?string $flatLevel): ServiceLevel
    {
        return match ($flatLevel) {
            'vip', 'priority' => ServiceLevel::ENTERPRISE,
            'standard' => ServiceLevel::PREMIUM,
            'low', null => ServiceLevel::STANDARD,
            default => ServiceLevel::STANDARD,
        };
    }
}

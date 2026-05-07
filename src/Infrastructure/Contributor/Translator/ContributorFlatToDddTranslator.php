<?php

declare(strict_types=1);

namespace App\Infrastructure\Contributor\Translator;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\Entity\Contributor as DddContributor;
use App\Domain\Contributor\ValueObject\ContractStatus;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Contributor\ValueObject\PersonName;
use App\Entity\Contributor as FlatContributor;
use DateTimeImmutable;
use RuntimeException;

/**
 * Anti-Corruption Layer translator (flat → DDD) for the Contributor BC.
 *
 * Stateless service.
 *
 * @see ADR-0008 ACL pattern
 */
final class ContributorFlatToDddTranslator
{
    public function translate(FlatContributor $flat): DddContributor
    {
        $id = ContributorId::fromLegacyInt(
            $flat->getId() ?? throw new RuntimeException('Cannot translate unsaved Contributor'),
        );

        $companyId = CompanyId::fromLegacyInt(
            $flat->getCompany()->getId() ?? throw new RuntimeException('Contributor has no company'),
        );

        $name = PersonName::fromParts($flat->getFirstName(), $flat->getLastName());

        $status = $flat->isActive() ? ContractStatus::ACTIVE : ContractStatus::INACTIVE;

        $managerId = null;
        if ($flat->getManager() !== null && $flat->getManager()->getId() !== null) {
            $managerId = ContributorId::fromLegacyInt($flat->getManager()->getId());
        }

        return DddContributor::reconstitute(
            id: $id,
            companyId: $companyId,
            name: $name,
            status: $status,
            extra: [
                'email' => $flat->getEmail(),
                'managerId' => $managerId,
                'createdAt' => new DateTimeImmutable(),
                'updatedAt' => null,
            ],
        );
    }
}

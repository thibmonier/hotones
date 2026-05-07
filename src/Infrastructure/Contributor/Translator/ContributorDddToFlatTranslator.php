<?php

declare(strict_types=1);

namespace App\Infrastructure\Contributor\Translator;

use App\Domain\Contributor\Entity\Contributor as DddContributor;
use App\Entity\Company;
use App\Entity\Contributor as FlatContributor;

/**
 * Anti-Corruption Layer translator (DDD → flat) for the Contributor BC.
 *
 * Stateless service.
 *
 * @see ADR-0008 ACL pattern
 */
final class ContributorDddToFlatTranslator
{
    public function applyTo(
        DddContributor $ddd,
        FlatContributor $flat,
        Company $company,
        ?FlatContributor $manager = null,
    ): void {
        $flat->setCompany($company);
        $flat->setFirstName($ddd->getName()->getFirstName());
        $flat->setLastName($ddd->getName()->getLastName());
        $flat->setEmail($ddd->getEmail());
        $flat->setActive($ddd->isActive());
        $flat->setManager($manager);
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OnboardingTemplate;
use App\Entity\Profile;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<OnboardingTemplate>
 */
class OnboardingTemplateRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, OnboardingTemplate::class, $companyContext);
    }

    /**
     * Find all active templates.
     *
     * @return OnboardingTemplate[]
     */
    public function findActive(): array
    {
        return $this->createCompanyQueryBuilder('ot')
            ->andWhere('ot.active = :active')
            ->setParameter('active', true)
            ->orderBy('ot.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find template by profile.
     */
    public function findByProfile(Profile $profile): ?OnboardingTemplate
    {
        return $this->createCompanyQueryBuilder('ot')
            ->andWhere('ot.profile = :profile')
            ->andWhere('ot.active = :active')
            ->setParameter('profile', $profile)
            ->setParameter('active', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find default template (no profile assigned).
     */
    public function findDefault(): ?OnboardingTemplate
    {
        return $this->createCompanyQueryBuilder('ot')
            ->andWhere('ot.profile IS NULL')
            ->andWhere('ot.active = :active')
            ->setParameter('active', true)
            ->orderBy('ot.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

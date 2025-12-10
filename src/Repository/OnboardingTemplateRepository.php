<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OnboardingTemplate;
use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OnboardingTemplate>
 */
class OnboardingTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OnboardingTemplate::class);
    }

    /**
     * Find all active templates.
     *
     * @return OnboardingTemplate[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('ot')
            ->where('ot.active = :active')
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
        return $this->createQueryBuilder('ot')
            ->where('ot.profile = :profile')
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
        return $this->createQueryBuilder('ot')
            ->where('ot.profile IS NULL')
            ->andWhere('ot.active = :active')
            ->setParameter('active', true)
            ->orderBy('ot.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

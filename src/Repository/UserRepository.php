<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<User>
 */
class UserRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, User::class, $companyContext);
    }

    /**
     * Find users by role.
     *
     * @return User[]
     */
    public function findByRole(string $role): array
    {
        return $this->createCompanyQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%')
            ->getQuery()
            ->getResult();
    }
}

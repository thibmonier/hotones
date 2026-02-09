<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Profile;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<Profile>
 */
class ProfileRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, Profile::class, $companyContext);
    }
}

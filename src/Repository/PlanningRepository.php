<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Planning;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<Planning>
 */
class PlanningRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, Planning::class, $companyContext);
    }
}

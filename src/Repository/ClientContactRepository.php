<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ClientContact;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<ClientContact>
 */
class ClientContactRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, ClientContact::class, $companyContext);
    }
}

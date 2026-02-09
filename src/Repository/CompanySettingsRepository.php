<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CompanySettings;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<CompanySettings>
 */
class CompanySettingsRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, CompanySettings::class, $companyContext);
    }

    /**
     * Récupère l'instance unique des paramètres pour la company courante.
     * Crée l'instance si elle n'existe pas.
     */
    public function getSettings(): CompanySettings
    {
        $company  = $this->companyContext->getCurrentCompany();
        $settings = $this->findOneBy(['company' => $company]);

        if (!$settings) {
            $settings = new CompanySettings();
            $settings->setCompany($company);
            $this->getEntityManager()->persist($settings);
            $this->getEntityManager()->flush();
        }

        return $settings;
    }
}

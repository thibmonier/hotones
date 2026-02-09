<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BoondManagerSettings;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<BoondManagerSettings>
 */
class BoondManagerSettingsRepository extends CompanyAwareRepository
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, BoondManagerSettings::class, $companyContext);
    }

    /**
     * Recupere l'instance unique des parametres BoondManager.
     * Cree l'instance si elle n'existe pas.
     */
    public function getSettings(): BoondManagerSettings
    {
        $settings = $this->findOneBy([]);

        if (!$settings) {
            $settings = new BoondManagerSettings();
            $settings->setCompany($this->companyContext->getCurrentCompany());
            $this->getEntityManager()->persist($settings);
            $this->getEntityManager()->flush();
        }

        return $settings;
    }

    /**
     * Trouve toutes les configurations activees qui ont besoin d'etre synchronisees.
     *
     * @return BoondManagerSettings[]
     */
    public function findNeedingSync(): array
    {
        return $this
            ->createQueryBuilder('b')
            ->where('b.enabled = :enabled')
            ->andWhere('b.autoSyncEnabled = :autoSyncEnabled')
            ->andWhere('b.apiBaseUrl IS NOT NULL')
            ->setParameter('enabled', true)
            ->setParameter('autoSyncEnabled', true)
            ->getQuery()
            ->getResult();
    }
}

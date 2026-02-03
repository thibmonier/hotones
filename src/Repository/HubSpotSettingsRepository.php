<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\HubSpotSettings;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<HubSpotSettings>
 */
class HubSpotSettingsRepository extends CompanyAwareRepository
{
    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, HubSpotSettings::class, $companyContext);
    }

    /**
     * Recupere l'instance unique des parametres HubSpot.
     * Cree l'instance si elle n'existe pas.
     */
    public function getSettings(): HubSpotSettings
    {
        $settings = $this->findOneBy([]);

        if (!$settings) {
            $settings = new HubSpotSettings();
            $settings->setCompany($this->companyContext->getCurrentCompany());
            $this->getEntityManager()->persist($settings);
            $this->getEntityManager()->flush();
        }

        return $settings;
    }

    /**
     * Trouve toutes les configurations activees qui ont besoin d'etre synchronisees.
     *
     * @return HubSpotSettings[]
     */
    public function findNeedingSync(): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.enabled = :enabled')
            ->andWhere('h.autoSyncEnabled = :autoSyncEnabled')
            ->andWhere('h.accessToken IS NOT NULL')
            ->setParameter('enabled', true)
            ->setParameter('autoSyncEnabled', true)
            ->getQuery()
            ->getResult();
    }
}

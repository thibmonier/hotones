<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CompanySettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanySettings>
 */
class CompanySettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanySettings::class);
    }

    /**
     * Récupère l'instance unique des paramètres
     * Crée l'instance si elle n'existe pas.
     */
    public function getSettings(): CompanySettings
    {
        $settings = $this->findOneBy([]);

        if (!$settings) {
            $settings = new CompanySettings();
            $this->getEntityManager()->persist($settings);
            $this->getEntityManager()->flush();
        }

        return $settings;
    }
}

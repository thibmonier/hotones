<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\EmployeeLevel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Crée les 12 niveaux d'emploi par défaut pour chaque entreprise.
 */
class EmployeeLevelFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Configuration des 12 niveaux avec fourchettes salariales (annuel brut en EUR).
     * Ces valeurs sont des exemples pour le marché français IT en 2025-2026.
     */
    private const LEVELS_CONFIG = [
        // Juniors (1-3)
        1 => [
            'name'         => 'Junior 1',
            'salaryMin'    => 28000,
            'salaryMax'    => 32000,
            'salaryTarget' => 30000,
            'tjm'          => 350,
            'color'        => '#90CAF9',
        ],
        2 => [
            'name'         => 'Junior 2',
            'salaryMin'    => 32000,
            'salaryMax'    => 36000,
            'salaryTarget' => 34000,
            'tjm'          => 400,
            'color'        => '#64B5F6',
        ],
        3 => [
            'name'         => 'Junior 3',
            'salaryMin'    => 36000,
            'salaryMax'    => 40000,
            'salaryTarget' => 38000,
            'tjm'          => 450,
            'color'        => '#42A5F5',
        ],

        // Expérimentés / Confirmés (4-6)
        4 => [
            'name'         => 'Confirmé 1',
            'salaryMin'    => 40000,
            'salaryMax'    => 45000,
            'salaryTarget' => 42500,
            'tjm'          => 500,
            'color'        => '#81C784',
        ],
        5 => [
            'name'         => 'Confirmé 2',
            'salaryMin'    => 45000,
            'salaryMax'    => 50000,
            'salaryTarget' => 47500,
            'tjm'          => 550,
            'color'        => '#66BB6A',
        ],
        6 => [
            'name'         => 'Confirmé 3',
            'salaryMin'    => 50000,
            'salaryMax'    => 55000,
            'salaryTarget' => 52500,
            'tjm'          => 600,
            'color'        => '#4CAF50',
        ],

        // Seniors (7-9)
        7 => [
            'name'         => 'Senior 1',
            'salaryMin'    => 55000,
            'salaryMax'    => 62000,
            'salaryTarget' => 58500,
            'tjm'          => 650,
            'color'        => '#FFB74D',
        ],
        8 => [
            'name'         => 'Senior 2',
            'salaryMin'    => 62000,
            'salaryMax'    => 70000,
            'salaryTarget' => 66000,
            'tjm'          => 700,
            'color'        => '#FFA726',
        ],
        9 => [
            'name'         => 'Senior 3',
            'salaryMin'    => 70000,
            'salaryMax'    => 78000,
            'salaryTarget' => 74000,
            'tjm'          => 750,
            'color'        => '#FF9800',
        ],

        // Leads / Experts (10-12)
        10 => [
            'name'         => 'Lead 1',
            'salaryMin'    => 78000,
            'salaryMax'    => 88000,
            'salaryTarget' => 83000,
            'tjm'          => 850,
            'color'        => '#E57373',
        ],
        11 => [
            'name'         => 'Lead 2',
            'salaryMin'    => 88000,
            'salaryMax'    => 100000,
            'salaryTarget' => 94000,
            'tjm'          => 950,
            'color'        => '#EF5350',
        ],
        12 => [
            'name'         => 'Expert',
            'salaryMin'    => 100000,
            'salaryMax'    => 120000,
            'salaryTarget' => 110000,
            'tjm'          => 1100,
            'color'        => '#F44336',
        ],
    ];

    private const LEVELS_DESCRIPTIONS = [
        1  => 'Débutant, moins de 2 ans d\'expérience. Travaille sous supervision, apprend les bases.',
        2  => '1-2 ans d\'expérience. Gagne en autonomie sur les tâches simples.',
        3  => '2-3 ans d\'expérience. Autonome sur les tâches courantes, prêt pour des responsabilités.',
        4  => '3-4 ans d\'expérience. Maîtrise les fondamentaux, commence à accompagner les juniors.',
        5  => '4-5 ans d\'expérience. Autonome sur des projets complets, force de proposition.',
        6  => '5-6 ans d\'expérience. Référent technique sur son domaine, accompagne les juniors.',
        7  => '6-8 ans d\'expérience. Expert technique, guide les décisions d\'architecture.',
        8  => '8-10 ans d\'expérience. Pilote des projets complexes, mentor reconnu.',
        9  => '10+ ans d\'expérience. Référent transverse, influence les standards de l\'équipe.',
        10 => 'Lead technique, coordonne une équipe, responsable des choix technologiques.',
        11 => 'Lead senior, gère plusieurs projets/équipes, interlocuteur des stakeholders.',
        12 => 'Expert/Architecte principal, définit la vision technique, rayonne en externe.',
    ];

    public function load(ObjectManager $manager): void
    {
        // Récupérer toutes les entreprises existantes
        $companies = $manager->getRepository(Company::class)->findAll();

        foreach ($companies as $company) {
            $this->createLevelsForCompany($manager, $company);
        }

        $manager->flush();
    }

    private function createLevelsForCompany(ObjectManager $manager, Company $company): void
    {
        foreach (self::LEVELS_CONFIG as $levelNumber => $config) {
            $level               = new EmployeeLevel();
            $level->company      = $company;
            $level->level        = $levelNumber;
            $level->name         = $config['name'];
            $level->description  = self::LEVELS_DESCRIPTIONS[$levelNumber];
            $level->salaryMin    = (string) $config['salaryMin'];
            $level->salaryMax    = (string) $config['salaryMax'];
            $level->salaryTarget = (string) $config['salaryTarget'];
            $level->targetTjm    = (string) $config['tjm'];
            $level->color        = $config['color'];
            $level->active       = true;

            $manager->persist($level);
        }
    }

    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
        ];
    }
}
